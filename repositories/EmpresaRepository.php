<?php
/**
 * EmpresaRepository.php
 * Encapsula queries de la tabla empresa.
 *
 * Reemplaza la logica suelta en api/get_empresa.php.
 */
require_once __DIR__ . '/../config/Repository.php';

class EmpresaRepository extends Repository
{
    protected string $table = 'empresa';

    /**
     * Get all active companies (ALL seasons — used for login dropdown).
     */
    public function getAllActive(int $estado = 1): array
    {
        return $this->fetchAll(
            "SELECT DISTINCT R_SOCIAL FROM {$this->table} WHERE estado = :estado",
            ['estado' => $estado]
        );
    }

    /**
     * Get all active companies for a given season.
     */
    public function getAllByTemporada(string $temporada, int $estado = 1): array
    {
        return $this->fetchAll(
            "SELECT * FROM {$this->table}
             WHERE temporada = :temporada AND estado = :estado
             ORDER BY R_SOCIAL",
            ['temporada' => $temporada, 'estado' => $estado]
        );
    }

    /**
     * Get a single company by its R_SOCIAL.
     */
    public function getByRSocial(string $rSocial): ?array
    {
        return $this->fetch(
            "SELECT * FROM {$this->table} WHERE R_SOCIAL = :r_social",
            ['r_social' => $rSocial]
        );
    }
}
