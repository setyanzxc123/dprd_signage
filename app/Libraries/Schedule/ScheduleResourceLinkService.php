<?php

namespace App\Libraries\Schedule;

use App\Models\JadwalBanmusModel;
use CodeIgniter\Database\BaseConnection;

final class ScheduleResourceLinkService
{
    public const SOURCE_BANMUS = 'banmus';

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    private readonly BaseConnection $db;

    public function publicUrl(string $source, int $id, string $resource): ?string
    {
        $row = $this->findResource($source, $id, $resource, true);
        if ($row === null) {
            return null;
        }

        $access = ScheduleResourceAccess::normalize(
            $row['resource_access'] ?? null,
            $resource === 'materi'
                ? ScheduleResourceAccess::PARTICIPANT
                : ScheduleResourceAccess::MEMBER,
        );

        return $access === ScheduleResourceAccess::PUBLIC
            ? $this->validExternalUrl($row['resource_url'] ?? null)
            : null;
    }

    public function memberUrl(
        string $source,
        int $id,
        string $resource,
        int $memberId,
    ): ?string {
        if ($memberId < 1) {
            return null;
        }

        $row = $this->findResource($source, $id, $resource, false);
        if ($row === null) {
            return null;
        }

        $access = ScheduleResourceAccess::normalize(
            $row['resource_access'] ?? null,
            $resource === 'materi'
                ? ScheduleResourceAccess::PARTICIPANT
                : ScheduleResourceAccess::MEMBER,
        );
        $isParticipant = $access === ScheduleResourceAccess::PARTICIPANT
            ? $this->memberParticipates($source, $id, $memberId)
            : false;
        if (! ScheduleResourceAccess::canView($access, true, $isParticipant)) {
            return null;
        }

        return $this->validExternalUrl($row['resource_url'] ?? null);
    }

    /**
     * @return array{resource_url: mixed, resource_access: mixed}|null
     */
    private function findResource(
        string $source,
        int $id,
        string $resource,
        bool $publicOnly,
    ): ?array {
        if ($id < 1 || ! in_array($resource, ['materi', 'stream'], true)) {
            return null;
        }

        $urlColumn = $resource . '_url';
        $accessColumn = $resource . '_akses';

        if ($source !== self::SOURCE_BANMUS) {
            return null;
        }

        $builder = $this->db->table('jadwal_banmus jb')
            ->select("jb.{$urlColumn} AS resource_url, jb.{$accessColumn} AS resource_access")
            ->join('dokumen_banmus db', 'db.id = jb.dokumen_banmus_id')
            ->where('jb.id', $id)
            ->where('jb.jenis_agenda', JadwalBanmusModel::TYPE_MEETING)
            ->whereIn('jb.status', JadwalBanmusModel::SCHEDULED_STATUSES)
            ->where('jb.deleted_at', null)
            ->where("jb.{$urlColumn} !=", '')
            ->where("jb.{$urlColumn} IS NOT NULL", null, false);
        if ($publicOnly) {
            $builder
                ->where('jb.publikasi', 'publik')
                ->where('db.is_publik', 1);
        }

        return $builder->get(1)->getRowArray();
    }

    private function memberParticipates(string $source, int $id, int $memberId): bool
    {
        if ($source === self::SOURCE_BANMUS) {
            return $this->db->table('jadwal_banmus_unit_rapat jbur')
                ->join('anggota_unit_rapat aur', 'aur.unit_rapat_id = jbur.unit_rapat_id')
                ->where('jbur.jadwal_banmus_id', $id)
                ->where('aur.anggota_id', $memberId)
                ->countAllResults() > 0;
        }

        return false;
    }

    private function validExternalUrl(mixed $value): ?string
    {
        $url = trim((string) $value);
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) ? $url : null;
    }
}
