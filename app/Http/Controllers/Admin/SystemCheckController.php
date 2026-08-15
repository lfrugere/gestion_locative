<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SystemCheckController extends Controller
{
    public function __invoke(): View
    {
        $uploadMaxSize = ini_get('upload_max_filesize');
        $postMaxSize = ini_get('post_max_size');
        $database = (string) config('database.connections.sqlite.database');
        $storagePath = (string) config('filesystems.disks.local.root');
        $requiredExtensions = [
            'ctype', 'curl', 'dom', 'fileinfo', 'filter', 'hash', 'mbstring', 'openssl',
            'pcre', 'pdo', 'pdo_sqlite', 'session', 'tokenizer', 'xml',
        ];
        $missingExtensions = array_values(array_filter(
            $requiredExtensions,
            fn (string $extension): bool => ! extension_loaded($extension),
        ));
        $runtimeStoragePaths = [
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
        ];
        $unwritableRuntimePaths = array_values(array_filter(
            $runtimeStoragePaths,
            fn (string $path): bool => ! is_dir($path) || ! is_writable($path),
        ));

        return view('admin.system-checks.index', [
            'checks' => [
                [
                    'label' => 'Version de PHP',
                    'value' => PHP_VERSION,
                    'ok' => version_compare(PHP_VERSION, '8.3.0', '>='),
                    'hint' => 'PHP 8.3 ou supérieur est requis.',
                ],
                [
                    'label' => 'Taille maximale d’un fichier',
                    'value' => $uploadMaxSize,
                    'ok' => $this->iniSizeToBytes($uploadMaxSize) >= 20 * 1024 * 1024,
                    'hint' => 'Définir upload_max_filesize à 20M au minimum.',
                ],
                [
                    'label' => 'Taille maximale de la requête',
                    'value' => $postMaxSize,
                    'ok' => $this->iniSizeToBytes($postMaxSize) >= 24 * 1024 * 1024,
                    'hint' => 'Définir post_max_size à 24M au minimum, puis redémarrer le serveur PHP.',
                ],
                [
                    'label' => 'Base SQLite',
                    'value' => $database,
                    'ok' => config('database.default') === 'sqlite' && is_file($database) && is_writable($database),
                    'hint' => 'Le fichier SQLite doit exister et être accessible en écriture.',
                ],
                [
                    'label' => 'Stockage privé des pièces jointes',
                    'value' => $storagePath,
                    'ok' => is_dir($storagePath) && is_writable($storagePath),
                    'hint' => 'Le répertoire d’archivage des photos et pièces jointes doit exister et être accessible en écriture.',
                ],
                [
                    'label' => 'Répertoires de travail Laravel',
                    'value' => $unwritableRuntimePaths === [] ? 'Tous accessibles' : implode(', ', $unwritableRuntimePaths),
                    'ok' => $unwritableRuntimePaths === [],
                    'hint' => 'Les répertoires cache, sessions, vues compilées et logs doivent être accessibles en écriture.',
                ],
                [
                    'label' => 'Extensions PHP requises',
                    'value' => $missingExtensions === [] ? 'Toutes les extensions requises sont actives' : 'Manquantes : '.implode(', ', $missingExtensions),
                    'ok' => $missingExtensions === [],
                    'hint' => 'Laravel requiert ses extensions système ; pdo_sqlite est requis par cette application.',
                ],
                [
                    'label' => 'Fuseau horaire',
                    'value' => (string) config('app.timezone'),
                    'ok' => config('app.timezone') === 'Europe/Paris',
                    'hint' => 'Le fuseau horaire doit être Europe/Paris.',
                ],
                [
                    'label' => 'Langue de l’application',
                    'value' => (string) config('app.locale'),
                    'ok' => config('app.locale') === 'fr',
                    'hint' => 'La langue de l’application doit être fr.',
                ],
            ],
        ]);
    }

    private function iniSizeToBytes(string|false $value): int
    {
        if ($value === false || $value === '') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $size = (int) $value;

        return match ($unit) {
            'g' => $size * 1024 * 1024 * 1024,
            'm' => $size * 1024 * 1024,
            'k' => $size * 1024,
            default => $size,
        };
    }
}
