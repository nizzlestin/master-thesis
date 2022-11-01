<?php

namespace App\MessageHandler;

use App\Message\JsonFileMergeMessage;
use App\Model\FileExtensions;
use App\Repository\RepoRepository;
use DateTime;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

#[AsMessageHandler]
class JsonFileMergeHandler
{
    private ParameterBagInterface $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    public function __invoke(JsonFileMergeMessage $mergeMessage)
    {
        $process = new Process(['repocleanup'], $this->parameterBag->get('app.repo_dir') . "/".$mergeMessage->getUuid());
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

}
