<?php
/**
 * Cache Helper
 *
 * Sistema de cache com suporte a Redis, Memcached e fallback para arquivos
 *
 * @version 1.0
 * @date 2025-11-08
 */

class CacheManager {
    private static $instance = null;
    private $redis = null;
    private $memcached = null;
    private $driver = 'file'; // redis, memcached, file
    private $cacheDir = __DIR__ . '/../storage/cache/';

    private function __construct() {
        // Tentar conectar ao Redis
        if (class_exists('Redis')) {
            try {
                $this->redis = new Redis();
                $connected = @$this->redis->connect(
                    getenv('REDIS_HOST') ?: '127.0.0.1',
                    getenv('REDIS_PORT') ?: 6379
                );

                if ($connected) {
                    // Autenticar se necessário
                    if ($password = getenv('REDIS_PASSWORD')) {
                        $this->redis->auth($password);
                    }

                    // Selecionar database
                    $this->redis->select(getenv('REDIS_DB') ?: 0);

                    $this->driver = 'redis';
                    return;
                }
            } catch (Exception $e) {
                error_log("Redis não disponível: " . $e->getMessage());
            }
        }

        // Tentar Memcached
        if (class_exists('Memcached')) {
            try {
                $this->memcached = new Memcached();
                $this->memcached->addServer(
                    getenv('MEMCACHED_HOST') ?: '127.0.0.1',
                    getenv('MEMCACHED_PORT') ?: 11211
                );

                // Testar conexão
                if ($this->memcached->getVersion()) {
                    $this->driver = 'memcached';
                    return;
                }
            } catch (Exception $e) {
                error_log("Memcached não disponível: " . $e->getMessage());
            }
        }

        // Fallback para cache de arquivos
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }

        $this->driver = 'file';
    }

    /**
     * Obtém instância singleton
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Obtém driver atual
     */
    public function getDriver() {
        return $this->driver;
    }

    /**
     * Armazena valor no cache
     *
     * @param string $key Chave
     * @param mixed $value Valor
     * @param int $ttl Tempo de vida em segundos (0 = sem expiração)
     * @return bool Sucesso
     */
    public function set($key, $value, $ttl = 3600) {
        $key = $this->normalizeKey($key);

        switch ($this->driver) {
            case 'redis':
                if ($ttl > 0) {
                    return $this->redis->setex($key, $ttl, serialize($value));
                } else {
                    return $this->redis->set($key, serialize($value));
                }

            case 'memcached':
                return $this->memcached->set($key, $value, $ttl);

            case 'file':
                return $this->setFile($key, $value, $ttl);

            default:
                return false;
        }
    }

    /**
     * Obtém valor do cache
     *
     * @param string $key Chave
     * @param mixed $default Valor padrão se não encontrado
     * @return mixed Valor ou padrão
     */
    public function get($key, $default = null) {
        $key = $this->normalizeKey($key);

        switch ($this->driver) {
            case 'redis':
                $value = $this->redis->get($key);
                return $value !== false ? unserialize($value) : $default;

            case 'memcached':
                $value = $this->memcached->get($key);
                return $value !== false ? $value : $default;

            case 'file':
                return $this->getFile($key, $default);

            default:
                return $default;
        }
    }

    /**
     * Verifica se chave existe no cache
     *
     * @param string $key Chave
     * @return bool Existe
     */
    public function has($key) {
        $key = $this->normalizeKey($key);

        switch ($this->driver) {
            case 'redis':
                return $this->redis->exists($key) > 0;

            case 'memcached':
                $this->memcached->get($key);
                return $this->memcached->getResultCode() !== Memcached::RES_NOTFOUND;

            case 'file':
                $file = $this->getCacheFilePath($key);
                if (!file_exists($file)) {
                    return false;
                }
                $data = unserialize(file_get_contents($file));
                return $data['expires'] === 0 || $data['expires'] > time();

            default:
                return false;
        }
    }

    /**
     * Remove item do cache
     *
     * @param string $key Chave
     * @return bool Sucesso
     */
    public function delete($key) {
        $key = $this->normalizeKey($key);

        switch ($this->driver) {
            case 'redis':
                return $this->redis->del($key) > 0;

            case 'memcached':
                return $this->memcached->delete($key);

            case 'file':
                $file = $this->getCacheFilePath($key);
                return file_exists($file) && @unlink($file);

            default:
                return false;
        }
    }

    /**
     * Limpa todo o cache
     *
     * @param string $pattern Padrão para limpar (apenas Redis)
     * @return bool Sucesso
     */
    public function flush($pattern = null) {
        switch ($this->driver) {
            case 'redis':
                if ($pattern) {
                    $keys = $this->redis->keys($pattern);
                    if (!empty($keys)) {
                        return $this->redis->del($keys) > 0;
                    }
                    return true;
                }
                return $this->redis->flushDB();

            case 'memcached':
                return $this->memcached->flush();

            case 'file':
                return $this->flushFiles($pattern);

            default:
                return false;
        }
    }

    /**
     * Obtém ou armazena valor (cache-aside pattern)
     *
     * @param string $key Chave
     * @param callable $callback Função para gerar valor
     * @param int $ttl TTL em segundos
     * @return mixed Valor
     */
    public function remember($key, callable $callback, $ttl = 3600) {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    /**
     * Incrementa valor no cache (contadores)
     *
     * @param string $key Chave
     * @param int $value Valor para incrementar
     * @return int Novo valor
     */
    public function increment($key, $value = 1) {
        $key = $this->normalizeKey($key);

        switch ($this->driver) {
            case 'redis':
                return $this->redis->incrBy($key, $value);

            case 'memcached':
                $result = $this->memcached->increment($key, $value);
                if ($result === false) {
                    $this->memcached->set($key, $value);
                    return $value;
                }
                return $result;

            case 'file':
                $current = (int)$this->get($key, 0);
                $new = $current + $value;
                $this->set($key, $new);
                return $new;

            default:
                return 0;
        }
    }

    /**
     * Decrementa valor no cache
     *
     * @param string $key Chave
     * @param int $value Valor para decrementar
     * @return int Novo valor
     */
    public function decrement($key, $value = 1) {
        return $this->increment($key, -$value);
    }

    /**
     * Define TTL de uma chave existente
     *
     * @param string $key Chave
     * @param int $ttl TTL em segundos
     * @return bool Sucesso
     */
    public function expire($key, $ttl) {
        $key = $this->normalizeKey($key);

        switch ($this->driver) {
            case 'redis':
                return $this->redis->expire($key, $ttl);

            case 'memcached':
                $value = $this->memcached->get($key);
                if ($value !== false) {
                    return $this->memcached->set($key, $value, $ttl);
                }
                return false;

            case 'file':
                $file = $this->getCacheFilePath($key);
                if (file_exists($file)) {
                    $data = unserialize(file_get_contents($file));
                    $data['expires'] = time() + $ttl;
                    return file_put_contents($file, serialize($data)) !== false;
                }
                return false;

            default:
                return false;
        }
    }

    /**
     * Normaliza chave do cache
     */
    private function normalizeKey($key) {
        // Adicionar prefixo da aplicação
        $prefix = getenv('CACHE_PREFIX') ?: 'app:';
        return $prefix . $key;
    }

    /**
     * Caminho do arquivo de cache
     */
    private function getCacheFilePath($key) {
        return $this->cacheDir . md5($key) . '.cache';
    }

    /**
     * Salvar em arquivo
     */
    private function setFile($key, $value, $ttl) {
        $file = $this->getCacheFilePath($key);
        $data = [
            'value' => $value,
            'expires' => $ttl > 0 ? time() + $ttl : 0
        ];

        return file_put_contents($file, serialize($data)) !== false;
    }

    /**
     * Obter de arquivo
     */
    private function getFile($key, $default) {
        $file = $this->getCacheFilePath($key);

        if (!file_exists($file)) {
            return $default;
        }

        $data = unserialize(file_get_contents($file));

        // Verificar expiração
        if ($data['expires'] > 0 && $data['expires'] < time()) {
            @unlink($file);
            return $default;
        }

        return $data['value'];
    }

    /**
     * Limpar arquivos de cache
     */
    private function flushFiles($pattern = null) {
        $files = glob($this->cacheDir . '*.cache');
        foreach ($files as $file) {
            @unlink($file);
        }
        return true;
    }

    /**
     * Obtém estatísticas do cache
     */
    public function getStats() {
        switch ($this->driver) {
            case 'redis':
                return $this->redis->info();

            case 'memcached':
                return $this->memcached->getStats();

            case 'file':
                $files = glob($this->cacheDir . '*.cache');
                return [
                    'driver' => 'file',
                    'total_items' => count($files),
                    'cache_dir' => $this->cacheDir
                ];

            default:
                return ['driver' => 'none'];
        }
    }
}

// ===== FUNÇÕES GLOBAIS DE CACHE =====

/**
 * Obtém valor do cache
 */
function cache_get($key, $default = null) {
    return CacheManager::getInstance()->get($key, $default);
}

/**
 * Define valor no cache
 */
function cache_set($key, $value, $ttl = 3600) {
    return CacheManager::getInstance()->set($key, $value, $ttl);
}

/**
 * Remove do cache
 */
function cache_delete($key) {
    return CacheManager::getInstance()->delete($key);
}

/**
 * Verifica se existe
 */
function cache_has($key) {
    return CacheManager::getInstance()->has($key);
}

/**
 * Obtém ou gera valor
 */
function cache_remember($key, callable $callback, $ttl = 3600) {
    return CacheManager::getInstance()->remember($key, $callback, $ttl);
}

/**
 * Incrementa contador
 */
function cache_increment($key, $value = 1) {
    return CacheManager::getInstance()->increment($key, $value);
}

/**
 * Limpa cache
 */
function cache_flush($pattern = null) {
    return CacheManager::getInstance()->flush($pattern);
}

/**
 * Obtém driver atual
 */
function cache_driver() {
    return CacheManager::getInstance()->getDriver();
}

/**
 * Exemplo de uso com cache de queries SQL
 */
function cached_query($sql, $params = [], $ttl = 600) {
    $cache_key = 'query:' . md5($sql . serialize($params));

    return cache_remember($cache_key, function() use ($sql, $params) {
        require_once __DIR__ . '/../config/database.php';
        $pdo = getDBConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }, $ttl);
}
