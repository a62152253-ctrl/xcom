<?php
// includes/pagination.php - Pagination helper
class Paginator {
    private $total;
    private $perPage;
    private $currentPage;

    public function __construct(int $total, int $perPage = 20, int $currentPage = 1) {
        $this->total = $total;
        $this->perPage = max(1, $perPage);
        $this->currentPage = max(1, $currentPage);
    }

    public function offset(): int {
        return ($this->currentPage - 1) * $this->perPage;
    }

    public function limit(): int {
        return $this->perPage;
    }

    public function totalPages(): int {
        return (int)ceil($this->total / $this->perPage);
    }

    public function hasNext(): bool {
        return $this->currentPage < $this->totalPages();
    }

    public function hasPrev(): bool {
        return $this->currentPage > 1;
    }

    public function currentPage(): int {
        return $this->currentPage;
    }

    public function links(): array {
        $pages = [];
        $total = $this->totalPages();
        for ($i = 1; $i <= $total; $i++) {
            $pages[] = $i;
        }
        return $pages;
    }
}
