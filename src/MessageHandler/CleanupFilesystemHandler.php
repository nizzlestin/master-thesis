<?php

namespace App\MessageHandler;

use App\Message\CleanupFilesystemMessage;
use App\Model\FileExtensions;
use App\Repository\ProjectRepository;
use DateTime;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

#[AsMessageHandler]
class CleanupFilesystemHandler
{
    private ParameterBagInterface $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    public function __invoke(CleanupFilesystemMessage $cleanupFilesystemMessage)
    {
        $process = new Process(['rm', '-rf', '--', $cleanupFilesystemMessage->getUuid()], $this->parameterBag->get('app.project_dir'));
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}
