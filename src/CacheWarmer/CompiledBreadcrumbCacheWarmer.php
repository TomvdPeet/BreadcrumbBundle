<?php

namespace TomvdPeet\BreadcrumbBundle\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use TomvdPeet\BreadcrumbBundle\Compiler\CompiledBreadcrumbMetadataCompiler;

final class CompiledBreadcrumbCacheWarmer implements CacheWarmerInterface
{
    public function __construct(
        private readonly CompiledBreadcrumbMetadataCompiler $compiler,
        private readonly string $cacheFile
    )
    {
    }

    public function isOptional(): bool
    {
        return false;
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $directory = \dirname($this->cacheFile);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents(
            $this->cacheFile,
            "<?php\n\nreturn ".var_export($this->compiler->compile(), true).";\n"
        );

        return [$this->cacheFile];
    }
}
