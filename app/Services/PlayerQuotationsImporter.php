<?php

namespace App\Services;

use App\Enums\PlayerRole;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class PlayerQuotationsImporter
{
    private const SHEET_NAME = 'Tutti';

    /**
     * Maps the source spreadsheet header labels to player attribute names.
     *
     * @var array<string, string>
     */
    private const COLUMN_MAP = [
        'Id' => 'fanta_id',
        'R' => 'role',
        'RM' => 'role_mantra',
        'Nome' => 'name',
        'Squadra' => 'team',
        'Qt.A' => 'current_quotation',
        'Qt.I' => 'initial_quotation',
        'Qt.A M' => 'current_quotation_mantra',
        'Qt.I M' => 'initial_quotation_mantra',
        'FVM' => 'fvm',
        'FVM M' => 'fvm_mantra',
    ];

    /**
     * Import teams and players from a "Quotazioni Fantacalcio" xlsx file.
     *
     * Players and teams no longer present in the file are removed, so a
     * re-import always leaves the database matching the uploaded file.
     *
     * @return array{teams: int, players_created: int, players_updated: int, players_deleted: int}
     */
    public function import(UploadedFile $file): array
    {
        $sheet = IOFactory::load($file->getRealPath())->getSheetByName(self::SHEET_NAME);

        if (! $sheet) {
            throw new RuntimeException(sprintf('Sheet "%s" not found in the uploaded file.', self::SHEET_NAME));
        }

        $rows = $sheet->toArray(null, true, true, false);
        $columns = $this->locateColumns($rows);

        $created = 0;
        $updated = 0;
        $deleted = 0;
        $seenFantaIds = [];

        DB::transaction(function () use ($rows, $columns, &$created, &$updated, &$deleted, &$seenFantaIds) {
            foreach ($rows as $row) {
                $fantaId = $row[$columns['fanta_id']] ?? null;

                if (! is_numeric($fantaId)) {
                    continue;
                }

                $team = $this->resolveTeam((string) ($row[$columns['team']] ?? ''));

                $player = Player::query()->updateOrCreate(
                    ['fanta_id' => (int) $fantaId],
                    [
                        'team_id' => $team?->id,
                        'name' => trim((string) ($row[$columns['name']] ?? '')),
                        'role' => PlayerRole::from(trim((string) $row[$columns['role']])),
                        'role_mantra' => $this->nullableString($row[$columns['role_mantra']] ?? null),
                        'initial_quotation' => (int) ($row[$columns['initial_quotation']] ?? 0),
                        'current_quotation' => (int) ($row[$columns['current_quotation']] ?? 0),
                        'initial_quotation_mantra' => $this->nullableInt($row[$columns['initial_quotation_mantra']] ?? null),
                        'current_quotation_mantra' => $this->nullableInt($row[$columns['current_quotation_mantra']] ?? null),
                        'fvm' => $this->nullableInt($row[$columns['fvm']] ?? null),
                        'fvm_mantra' => $this->nullableInt($row[$columns['fvm_mantra']] ?? null),
                    ]
                );

                $seenFantaIds[] = $player->fanta_id;

                $player->wasRecentlyCreated ? $created++ : $updated++;
            }

            $deleted = Player::query()->whereNotIn('fanta_id', $seenFantaIds)->delete();

            Team::query()->doesntHave('players')->delete();
        });

        return [
            'teams' => Team::query()->count(),
            'players_created' => $created,
            'players_updated' => $updated,
            'players_deleted' => $deleted,
        ];
    }

    private function resolveTeam(string $name): ?Team
    {
        $name = trim($name);

        if ($name === '') {
            return null;
        }

        return Team::query()->firstOrCreate(['name' => $name]);
    }

    /**
     * Find the header row (containing the expected column labels) and map
     * each expected column to its index in the row arrays.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @return array<string, int>
     */
    private function locateColumns(array &$rows): array
    {
        foreach ($rows as $index => $row) {
            if (in_array('Id', $row, true) && in_array('Nome', $row, true)) {
                $columns = $this->mapHeader($row);
                $rows = array_slice($rows, $index + 1);

                return $columns;
            }
        }

        throw new RuntimeException('Could not locate the header row (expected "Id" and "Nome" columns).');
    }

    /**
     * @param  array<int, mixed>  $header
     * @return array<string, int>
     */
    private function mapHeader(array $header): array
    {
        $labelToIndex = array_flip(array_map(static fn (mixed $value): string => trim((string) $value), $header));

        $columns = [];

        foreach (self::COLUMN_MAP as $label => $field) {
            if (! isset($labelToIndex[$label])) {
                throw new RuntimeException(sprintf('Missing expected column "%s" in the uploaded file.', $label));
            }

            $columns[$field] = $labelToIndex[$label];
        }

        return $columns;
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
