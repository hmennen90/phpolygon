<?php

declare(strict_types=1);

namespace PHPolygon\Geometry;

class MeshCacheIO
{
    private const MAGIC = 'PHMC';
    private const FORMAT_VERSION = 1;
    private const HEADER_SIZE = 28;

    public static function encode(MeshData $mesh, string $version): string
    {
        $header = pack(
            'a4vvVVVVV',
            self::MAGIC,
            self::FORMAT_VERSION,
            0, // reserved
            self::versionHash($version),
            count($mesh->vertices),
            count($mesh->normals),
            count($mesh->uvs),
            count($mesh->indices),
        );

        $data = pack('g*', ...$mesh->vertices);
        $data .= pack('g*', ...$mesh->normals);
        $data .= pack('g*', ...$mesh->uvs);

        if (count($mesh->indices) > 0) {
            $data .= pack('V*', ...$mesh->indices);
        }

        return $header . $data;
    }

    public static function decode(string $data): ?MeshData
    {
        if (strlen($data) < self::HEADER_SIZE) {
            return null;
        }

        $header = unpack(
            'a4magic/vformatVersion/vreserved/VversionHash/VvertexCount/VnormalCount/VuvCount/VindexCount',
            substr($data, 0, self::HEADER_SIZE),
        );

        if ($header === false || $header['magic'] !== self::MAGIC) {
            return null;
        }

        if ($header['formatVersion'] !== self::FORMAT_VERSION) {
            return null;
        }

        /** @var array{vertexCount: int, normalCount: int, uvCount: int, indexCount: int} $header */
        $vertexCount = $header['vertexCount'];
        $normalCount = $header['normalCount'];
        $uvCount = $header['uvCount'];
        $indexCount = $header['indexCount'];

        $expectedSize = self::HEADER_SIZE
            + ($vertexCount * 4)
            + ($normalCount * 4)
            + ($uvCount * 4)
            + ($indexCount * 4);

        if (strlen($data) < $expectedSize) {
            return null;
        }

        $offset = self::HEADER_SIZE;

        $vertices = self::unpackFloats($data, $offset, $vertexCount);
        $offset += $vertexCount * 4;

        $normals = self::unpackFloats($data, $offset, $normalCount);
        $offset += $normalCount * 4;

        $uvs = self::unpackFloats($data, $offset, $uvCount);
        $offset += $uvCount * 4;

        if ($indexCount > 0) {
            $unpacked = unpack('V' . $indexCount, substr($data, $offset, $indexCount * 4));
            /** @var int[] $indices */
            $indices = $unpacked !== false ? array_values($unpacked) : [];
        } else {
            $indices = [];
        }

        return new MeshData($vertices, $normals, $uvs, $indices);
    }

    public static function readVersionHash(string $data): ?int
    {
        if (strlen($data) < 12) {
            return null;
        }

        $header = unpack('a4magic/vformatVersion/vreserved/VversionHash', substr($data, 0, 12));

        if ($header === false || $header['magic'] !== self::MAGIC) {
            return null;
        }

        /** @var array{versionHash: int} $header */
        return $header['versionHash'];
    }

    public static function versionHash(string $version): int
    {
        return crc32($version) & 0xFFFFFFFF;
    }

    /**
     * @return float[]
     */
    private static function unpackFloats(string $data, int $offset, int $count): array
    {
        if ($count === 0) {
            return [];
        }

        $unpacked = unpack('g' . $count, substr($data, $offset, $count * 4));

        /** @var float[] */
        return $unpacked !== false ? array_values($unpacked) : [];
    }
}
