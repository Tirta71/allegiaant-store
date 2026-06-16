<?php

namespace App\Services;

class QrisPayloadService
{
    public function dynamicPayload(int $amount): ?string
    {
        $payload = preg_replace('/[\r\n]+/', '', trim((string) config('payment.qris.static_payload')));

        if ($payload === '') {
            return null;
        }

        $fields = $this->parse($payload);

        if ($fields === []) {
            return null;
        }

        $fields = array_values(array_filter(
            $fields,
            fn (array $field) => ! in_array($field['tag'], ['54', '63'], true)
        ));

        $this->setOrInsertTag($fields, '01', '12', '00');
        $this->insertTagBefore($fields, '54', (string) max(0, $amount), ['58', '59', '60']);

        $payloadWithoutCrc = $this->build($fields).'6304';

        return $payloadWithoutCrc.$this->crc16($payloadWithoutCrc);
    }

    /**
     * @return array<int, array{tag: string, value: string}>
     */
    private function parse(string $payload): array
    {
        $fields = [];
        $offset = 0;
        $length = strlen($payload);

        while ($offset + 4 <= $length) {
            $tag = substr($payload, $offset, 2);
            $valueLength = substr($payload, $offset + 2, 2);

            if (! ctype_digit($valueLength)) {
                return [];
            }

            $offset += 4;
            $valueLength = (int) $valueLength;

            if ($offset + $valueLength > $length) {
                return [];
            }

            $fields[] = [
                'tag' => $tag,
                'value' => substr($payload, $offset, $valueLength),
            ];

            $offset += $valueLength;
        }

        return $offset === $length ? $fields : [];
    }

    /**
     * @param  array<int, array{tag: string, value: string}>  $fields
     */
    private function build(array $fields): string
    {
        return collect($fields)
            ->map(fn (array $field) => $field['tag'].str_pad((string) strlen($field['value']), 2, '0', STR_PAD_LEFT).$field['value'])
            ->implode('');
    }

    /**
     * @param  array<int, array{tag: string, value: string}>  $fields
     */
    private function setOrInsertTag(array &$fields, string $tag, string $value, string $afterTag): void
    {
        foreach ($fields as &$field) {
            if ($field['tag'] === $tag) {
                $field['value'] = $value;

                return;
            }
        }

        $index = $this->findIndex($fields, $afterTag);
        array_splice($fields, $index === null ? 0 : $index + 1, 0, [[
            'tag' => $tag,
            'value' => $value,
        ]]);
    }

    /**
     * @param  array<int, array{tag: string, value: string}>  $fields
     * @param  array<int, string>  $beforeTags
     */
    private function insertTagBefore(array &$fields, string $tag, string $value, array $beforeTags): void
    {
        $insertAt = count($fields);

        foreach ($beforeTags as $beforeTag) {
            $index = $this->findIndex($fields, $beforeTag);

            if ($index !== null) {
                $insertAt = $index;
                break;
            }
        }

        array_splice($fields, $insertAt, 0, [[
            'tag' => $tag,
            'value' => $value,
        ]]);
    }

    /**
     * @param  array<int, array{tag: string, value: string}>  $fields
     */
    private function findIndex(array $fields, string $tag): ?int
    {
        foreach ($fields as $index => $field) {
            if ($field['tag'] === $tag) {
                return $index;
            }
        }

        return null;
    }

    private function crc16(string $payload): string
    {
        $crc = 0xFFFF;
        $length = strlen($payload);

        for ($i = 0; $i < $length; $i++) {
            $crc ^= ord($payload[$i]) << 8;

            for ($bit = 0; $bit < 8; $bit++) {
                $crc = ($crc & 0x8000)
                    ? (($crc << 1) ^ 0x1021)
                    : ($crc << 1);
                $crc &= 0xFFFF;
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
