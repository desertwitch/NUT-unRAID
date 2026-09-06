<?

function nut_resolve_config_file($path, $base = '/etc/nut/') {
    if (!is_string($path) || $path === '') {
        return false;
    }

    $resolvedBase = realpath($base);
    $resolvedPath = realpath($path);

    if ($resolvedBase === false || $resolvedPath === false || !is_file($resolvedPath)) {
        return false;
    }

    $basePrefix = rtrim($resolvedBase, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (strncmp($resolvedPath, $basePrefix, strlen($basePrefix)) !== 0) {
        return false;
    }

    $extension = strtolower(pathinfo($resolvedPath, PATHINFO_EXTENSION));
    if (!in_array($extension, ['conf', 'users', 'sh'], true)) {
        return false;
    }

    return $resolvedPath;
}

?>
