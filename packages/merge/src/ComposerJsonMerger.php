<?php

declare(strict_types=1);

namespace Symplify\MonorepoBuilder\Merge;

use Symplify\MonorepoBuilder\ComposerJsonObject\ComposerJsonFactory;
use Symplify\MonorepoBuilder\ComposerJsonObject\ValueObject\ComposerJson;
use Symplify\MonorepoBuilder\Merge\Configuration\MergedPackagesCollector;
use Symplify\MonorepoBuilder\Merge\Contract\ComposerKeyMergerInterface;
use Symplify\MonorepoBuilder\Merge\PathResolver\AutoloadPathNormalizer;
use Symplify\SmartFileSystem\SmartFileInfo;

final class ComposerJsonMerger
{
    /**
     * @var ComposerKeyMergerInterface[]
     */
    private $composerKeyMergers = [];

    /**
     * @var MergedPackagesCollector
     */
    private $mergedPackagesCollector;

    /**
     * @var AutoloadPathNormalizer
     */
    private $autoloadPathNormalizer;

    /**
     * @var ComposerJsonFactory
     */
    private $composerJsonFactory;

    /**
     * @param ComposerKeyMergerInterface[] $composerKeyMergers
     */
    public function __construct(
        ComposerJsonFactory $composerJsonFactory,
        MergedPackagesCollector $mergedPackagesCollector,
        AutoloadPathNormalizer $autoloadPathNormalizer,
        array $composerKeyMergers
    ) {
        $this->mergedPackagesCollector = $mergedPackagesCollector;
        $this->autoloadPathNormalizer = $autoloadPathNormalizer;
        $this->composerJsonFactory = $composerJsonFactory;
        $this->composerKeyMergers = $composerKeyMergers;
    }

    /**
     * @param SmartFileInfo[] $composerPackageFileInfos
     */
    public function mergeFileInfos(array $composerPackageFileInfos): ComposerJson
    {
        $mainComposerJson = new ComposerJson();
        foreach ($composerPackageFileInfos as $packageFileInfo) {
            $packageComposerJson = $this->composerJsonFactory->createFromFileInfo($packageFileInfo);

            $this->mergeJsonToRoot($mainComposerJson, $packageComposerJson, $packageFileInfo);
        }

        return $mainComposerJson;
    }

    public function mergeJsonToRoot(
        ComposerJson $mainComposerJson,
        ComposerJson $newComposerJson,
        ?SmartFileInfo $packageFileInfo = null
    ): void {
        if ($newComposerJson->getName()) {
            $this->mergedPackagesCollector->addPackage($newComposerJson->getName());
        }

        // prepare paths before autolaod merging
        if ($packageFileInfo !== null) {
            $this->autoloadPathNormalizer->normalizeAutoloadPaths($newComposerJson, $packageFileInfo);
        }

        foreach ($this->composerKeyMergers as $composerKeyMerger) {
            $composerKeyMerger->merge($mainComposerJson, $newComposerJson);
        }
    }
}
