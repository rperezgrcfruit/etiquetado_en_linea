<?php
/**
 * Repository.php — Base repository class for data access.
 *
 * Provides convenient query methods built on Database::getInstance().
 * Subclasses define their own query methods.
 *
 * Usage:
 *   class ProcesoRepository extends Repository {
 *       public function getByProceso($n_proceso) {
 *           return $this->db->fetchAll(
 *               "SELECT * FROM procesos WHERE n_proceso = :n_proceso",
 *               ['n_proceso' => $n_proceso, 'empresa' => $_SESSION['empresa']]
 *           );
 *       }
 *   }
 */
require_once __DIR__ . '/Database.php';

abstract class Repository
{
    protected Database $db;
    protected string $table;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Fetch all rows matching the query.
     */
    protected function fetchAll(string $sql, array $params = []): array
    {
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Fetch a single row.
     */
    protected function fetch(string $sql, array $params = []): ?array
    {
        return $this->db->fetch($sql, $params);
    }

    /**
     * Execute INSERT/UPDATE/DELETE and return affected rows.
     */
    protected function execute(string $sql, array $params = []): int
    {
        return $this->db->execute($sql, $params);
    }

    /**
     * Get the next available folio from asignacion_folios (transaction-safe).
     */
    protected function getNextFolio(string $tipoFolio, string $empresa, string $temporada, string $estado = 'DISPONIBLE'): ?string
    {
        //echo "Fetching next folio for tipo {$tipoFolio}, empresa {$empresa}, temporada {$temporada}...\n";
        $this->db->beginTransaction();
        try {
            $sql = "SELECT folio FROM asignacion_folios
                    WHERE empresa = ? AND temporada = ? AND tipo_folio = ? AND estado_folio = ?
                    LIMIT 1 FOR UPDATE";
            $stmt = $this->db->query($sql, [$empresa, $temporada, $tipoFolio, $estado]);
            $row = $stmt->fetch();
            if ($row) {
                $this->db->commit();
                return $row['folio'];
            }
            $this->db->rollBack();
            return null;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Mark a folio as used.
     */
    protected function useFolio(string $folio, string $empresa, string $temporada): int
    {
        return $this->execute(
            "UPDATE asignacion_folios SET estado_folio = 'UTILIZADO'
             WHERE folio = :folio AND empresa = :empresa AND temporada = :temporada",
            ['folio' => $folio, 'empresa' => $empresa, 'temporada' => $temporada]
        );
    }
}
