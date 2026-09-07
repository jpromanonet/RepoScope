<?php

declare(strict_types=1);

final class GitHubClient
{
    private string $token;
    private string $userAgent = 'RepoScope/0.1';

    public function __construct(string $token)
    {
        $this->token = trim($token);
        if ($this->token === '') {
            throw new RuntimeException('Falta el token de GitHub. Cargalo en Configuración o en .env (GITHUB_TOKEN).');
        }
    }

    public function authenticatedUser(): array
    {
        return $this->get('/user');
    }

    public function listOwnerRepos(): array
    {
        return $this->paginate('/user/repos', [
            'per_page' => '100',
            'affiliation' => 'owner,collaborator,organization_member',
            'sort' => 'full_name',
            'direction' => 'asc',
        ]);
    }

    public function listOrgRepos(string $org): array
    {
        $org = trim($org);
        if ($org === '') {
            return [];
        }
        return $this->paginate('/orgs/' . rawurlencode($org) . '/repos', [
            'per_page' => '100',
            'type' => 'all',
            'sort' => 'full_name',
        ]);
    }

    public function rootContents(string $owner, string $repo, ?string $ref = null): array
    {
        $path = '/repos/' . rawurlencode($owner) . '/' . rawurlencode($repo) . '/contents/';
        $query = [];
        if ($ref) {
            $query['ref'] = $ref;
        }
        try {
            $data = $this->get($path, $query);
        } catch (RuntimeException $e) {
            if (str_contains($e->getMessage(), 'HTTP 404') || str_contains($e->getMessage(), 'HTTP 409')) {
                return [];
            }
            throw $e;
        }
        return is_array($data) ? $data : [];
    }

    private function paginate(string $path, array $query = []): array
    {
        $items = [];
        $page = 1;
        do {
            $query['page'] = (string) $page;
            $batch = $this->get($path, $query);
            if (!is_array($batch) || $batch === []) {
                break;
            }
            foreach ($batch as $row) {
                if (is_array($row)) {
                    $items[] = $row;
                }
            }
            $page++;
        } while (count($batch) >= 100 && $page <= 20);

        return $items;
    }

    private function get(string $path, array $query = []): array
    {
        $url = 'https://api.github.com' . $path;
        if ($query) {
            $url .= '?' . http_build_query($query);
        }

        $headers = [
            'Accept: application/vnd.github+json',
            'X-GitHub-Api-Version: 2022-11-28',
            'User-Agent: ' . $this->userAgent,
            'Authorization: Bearer ' . $this->token,
        ];

        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP necesita la extensión curl para hablar con GitHub.');
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new RuntimeException('No se pudo contactar GitHub: ' . $err);
        }

        $decoded = json_decode((string) $body, true);
        if ($code === 401) {
            throw new RuntimeException('Token de GitHub inválido o expirado.');
        }
        if ($code === 403) {
            $msg = is_array($decoded) ? (string) ($decoded['message'] ?? 'acceso denegado') : 'acceso denegado';
            throw new RuntimeException('GitHub rechazó la petición (403): ' . $msg);
        }
        if ($code === 404 || $code === 409) {
            throw new RuntimeException('HTTP ' . $code);
        }
        if ($code >= 400) {
            $msg = is_array($decoded) ? (string) ($decoded['message'] ?? "HTTP {$code}") : "HTTP {$code}";
            throw new RuntimeException('Error de GitHub: ' . $msg);
        }
        if (!is_array($decoded)) {
            throw new RuntimeException('Respuesta inválida de GitHub.');
        }

        return $decoded;
    }
}
