<?php

declare(strict_types=1);

namespace App\Service;

final class PaginationService
{
    /**
     * @param array<string, scalar> $extraQuery query parameters kept in the page links
     * @return array{page: int, pages: int, offset: int, prevUrl: ?string, nextUrl: ?string}
     */
    public function build(int $total, int $perPage, int $requestedPage, string $baseUrl, array $extraQuery = []): array
    {
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $requestedPage), $pages);
        $link = fn(int $p): string => $baseUrl . '?' . http_build_query($extraQuery + ['page' => $p]);

        return [
            'page' => $page,
            'pages' => $pages,
            'offset' => ($page - 1) * $perPage,
            'prevUrl' => $page > 1 ? $link($page - 1) : null,
            'nextUrl' => $page < $pages ? $link($page + 1) : null,
        ];
    }
}
