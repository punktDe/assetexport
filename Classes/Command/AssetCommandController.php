<?php
declare(strict_types=1);

namespace PunktDe\AssetExport\Command;

/*
 *  (c) 2020 punkt.de GmbH - Karlsruhe, Germany - https://punkt.de
 *  All rights reserved.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Cli\Exception\StopCommandException;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Utility\Exception\FilesException;
use Neos\Utility\Files;

class AssetCommandController extends CommandController
{

    /**
     * @Flow\Inject
     */
    protected AssetRepository $assetRepository;

    /**
     * Takes care of exporting all assets stored in Neos as files. This can be very helpful for backups.
     *
     * @param string $targetPath The path where the resource files should be exported to.
     * @param bool $emptyTargetPath If set, all files in the target path will be deleted before the export runs
     * @throws FilesException
     * @throws StopCommandException
     */
    public function exportCommand(string $targetPath, bool $emptyTargetPath = false): void
    {
        if (!is_dir($targetPath)) {
            $this->outputLine('The target path does not exist.');
            $this->quit(1);
        }

        $targetPath = realpath($targetPath) . '/';

        if ($emptyTargetPath) {
            $files = Files::readDirectoryRecursively($targetPath);

            if (count($files) && $this->output->askConfirmation(sprintf('Are you sure you want to delete %s files in %s ?', count($files), $targetPath), false)) {
                $this->outputLine('<b>Removing all files in %s ...</b>' . chr(10), [$targetPath]);
                Files::emptyDirectoryRecursively($targetPath);
            }
        }

        $this->outputLine('<b>Exporting resources to %s ...</b>' . chr(10), [$targetPath]);

        foreach ($this->assetRepository->iterate($this->assetRepository->findAllIterator()) as $asset) {
            /** @var Asset $asset */
            $resource = $asset->getResource();
            $stream = $resource->getStream();
            if ($stream === false) {
                $this->outputLine('<error>missing</error>  %s', [$resource->getSha1(), $resource->getFilename()]);
            } else {
                $fileName = substr($resource->getFilename(), 0,  strpos($resource->getFilename(), '.'));
                $fileEnding = substr($resource->getFilename(), strpos($resource->getFilename(), '.'));
                $sha1 = substr($resource->getSha1(), 0,  10);
                $separator = '_';
                file_put_contents($targetPath . $fileName . $separator . $sha1 . $fileEnding, $stream);
                $this->outputLine('<success>exported</success> %s', [$targetPath . $fileName . $separator . $sha1 . $fileEnding]);
            }
        }
    }
}
