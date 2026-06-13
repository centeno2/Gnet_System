<?php

namespace App\Services\Mantenimiento;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class DatabaseBackupService
{
    private string $disk = 'local';

    private string $directorio = 'respaldos';

    public function crearRespaldo(string $prefijo = 'gnet_respaldo'): string
    {
        Storage::disk($this->disk)->makeDirectory($this->directorio);

        $nombre = $prefijo . '_' . Carbon::now()->format('Ymd_His') . '.sql';
        $archivo = $this->directorio . '/' . $nombre;
        $ruta = Storage::disk($this->disk)->path($archivo);

        File::ensureDirectoryExists(dirname($ruta));

        $this->ejecutarDump($ruta);

        if (! File::exists($ruta) || File::size($ruta) <= 0) {
            Storage::disk($this->disk)->delete($archivo);

            throw new RuntimeException('El respaldo se generó vacío. Revisa la conexión o el comando mysqldump.');
        }

        return $archivo;
    }

    public function restaurarDesdeStorage(string $archivo, bool $crearRespaldoPrevio = true): void
    {
        $archivo = $this->normalizarArchivo($archivo);

        if (! Storage::disk($this->disk)->exists($archivo)) {
            throw new RuntimeException('El archivo de respaldo seleccionado no existe.');
        }

        $ruta = Storage::disk($this->disk)->path($archivo);

        if ($crearRespaldoPrevio) {
            $this->crearRespaldo('gnet_pre_restauracion');
        }

        $this->ejecutarRestauracion($ruta);
    }

    public function listarRespaldos(): array
    {
        Storage::disk($this->disk)->makeDirectory($this->directorio);

        return collect(Storage::disk($this->disk)->allFiles($this->directorio))
            ->filter(fn (string $archivo) => Str::endsWith(Str::lower($archivo), '.sql'))
            ->map(function (string $archivo) {
                return [
                    'archivo' => $archivo,
                    'nombre' => basename($archivo),
                    'tamano' => $this->formatearTamano(Storage::disk($this->disk)->size($archivo)),
                    'fecha' => Carbon::createFromTimestamp(
                        Storage::disk($this->disk)->lastModified($archivo)
                    )->format('d/m/Y h:i A'),
                ];
            })
            ->sortByDesc('fecha')
            ->values()
            ->all();
    }

    public function eliminarRespaldo(string $archivo): void
    {
        $archivo = $this->normalizarArchivo($archivo);

        if (! Storage::disk($this->disk)->exists($archivo)) {
            throw new RuntimeException('El respaldo que intentas eliminar no existe.');
        }

        Storage::disk($this->disk)->delete($archivo);
    }

    private function ejecutarDump(string $ruta): void
    {
        $config = $this->configuracionMysql();

        $command = [
            env('MYSQLDUMP_BINARY', 'mysqldump'),
            '--user=' . $config['username'],
            '--default-character-set=utf8mb4',
            '--single-transaction',
            '--routines',
            '--triggers',
            '--events',
            '--hex-blob',
        ];

        if (! empty($config['unix_socket'])) {
            $command[] = '--socket=' . $config['unix_socket'];
        } else {
            $command[] = '--host=' . $config['host'];
            $command[] = '--port=' . $config['port'];
        }

        $command[] = $config['database'];

        $handle = fopen($ruta, 'wb');

        if ($handle === false) {
            throw new RuntimeException('No se pudo preparar el archivo de respaldo.');
        }

        $process = new Process($command, base_path(), $this->variablesEntornoMysql($config));
        $process->setTimeout(300);

        $process->run(function (string $type, string $buffer) use ($handle) {
            if ($type === Process::OUT) {
                fwrite($handle, $buffer);
            }
        });

        fclose($handle);

        if (! $process->isSuccessful()) {
            File::delete($ruta);

            throw new RuntimeException($this->mensajeErrorProceso(
                $process,
                'No se pudo crear el respaldo de la base de datos.'
            ));
        }
    }

    private function ejecutarRestauracion(string $ruta): void
    {
        if (! File::exists($ruta)) {
            throw new RuntimeException('El archivo SQL no existe.');
        }

        $config = $this->configuracionMysql();

        $command = [
            env('MYSQL_BINARY', 'mysql'),
            '--user=' . $config['username'],
            '--default-character-set=utf8mb4',
        ];

        if (! empty($config['unix_socket'])) {
            $command[] = '--socket=' . $config['unix_socket'];
        } else {
            $command[] = '--host=' . $config['host'];
            $command[] = '--port=' . $config['port'];
        }

        $command[] = $config['database'];

        $input = fopen($ruta, 'rb');

        if ($input === false) {
            throw new RuntimeException('No se pudo leer el archivo SQL seleccionado.');
        }

        $process = new Process($command, base_path(), $this->variablesEntornoMysql($config));
        $process->setInput($input);
        $process->setTimeout(300);

        $process->run();

        fclose($input);

        if (! $process->isSuccessful()) {
            throw new RuntimeException($this->mensajeErrorProceso(
                $process,
                'No se pudo restaurar la base de datos.'
            ));
        }
    }

    private function configuracionMysql(): array
    {
        $conexion = config('database.default');
        $config = config("database.connections.{$conexion}");

        if (($config['driver'] ?? null) !== 'mysql') {
            throw new RuntimeException('El mantenimiento está configurado para bases de datos MySQL.');
        }

        if (empty($config['database'])) {
            throw new RuntimeException('No se encontró el nombre de la base de datos.');
        }

        return [
            'host' => $config['host'] ?? '127.0.0.1',
            'port' => $config['port'] ?? '3306',
            'database' => $config['database'],
            'username' => $config['username'] ?? 'root',
            'password' => $config['password'] ?? '',
            'unix_socket' => $config['unix_socket'] ?? '',
        ];
    }

    private function variablesEntornoMysql(array $config): array
    {
        return filled($config['password'])
            ? ['MYSQL_PWD' => $config['password']]
            : [];
    }

    private function normalizarArchivo(string $archivo): string
    {
        $archivo = Str::replace('\\', '/', $archivo);
        $archivo = ltrim($archivo, '/');

        if (Str::contains($archivo, ['..', "\0"])) {
            throw new RuntimeException('Ruta de respaldo no válida.');
        }

        if (! Str::startsWith($archivo, $this->directorio . '/')) {
            throw new RuntimeException('El archivo debe estar dentro de la carpeta de respaldos.');
        }

        if (! Str::endsWith(Str::lower($archivo), '.sql')) {
            throw new RuntimeException('Solo se permiten archivos SQL.');
        }

        return $archivo;
    }

    private function mensajeErrorProceso(Process $process, string $mensajeBase): string
    {
        $error = trim($process->getErrorOutput());

        if ($error === '') {
            return $mensajeBase;
        }

        return $mensajeBase . ' Detalle: ' . Str::limit($error, 300);
    }

    private function formatearTamano(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}