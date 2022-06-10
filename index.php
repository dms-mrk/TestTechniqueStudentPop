<?php

class EnsureValid {
    public static function ensureScore(int $score): int
    {
        if ($score < 0 || $score > 100) {
            throw new ErrorException('Score should be between 1 and 100');
        }

        return $score;
    }
}


class ProposalUser {
    private int $userId;
    private ?string $proposalMessage;
    private int $score;
    private ?string $type;
    private ?DateTime $sendAt;

    public function __construct(
        int $userId,
        int $score,
        ?string $type,
        ?string $proposalMessage,
    ) {
        $this->userId = $userId;
        $this->proposalMessage = $proposalMessage;
        $this->type = $type;
        $this->setScore($score);
        $this->sendAt = null;
    }

    public function editSendAt
    (
        DateTime $sendAt,
    ): void {

        $this->sendAt = $sendAt;
    }

    public function readScore(): int
    {
        return $this->score;
    }

    private function setScore(int $score): self
    {
        $this->score = EnsureValid::ensureScore($score);

        return $this;
    }
}

/**
 * @param ProposalUser[] $proposals
 * @param DateTime $deadline
 * @param int $proposalsNumberMINByWave
 * @return array
 * @throws Exception
 */
function scheduleProposalsBy(array $proposals, DateTime $deadline, int $proposalsNumberMINByWave): array
{
    $proposalsNumber = count($proposals);

    if($proposalsNumberMINByWave === 0)
    {
        $proposalsNumberMINByWave = 1;
    }

    $waveTime = 300; // 5 min
    $now = time(); // Current TIMESTAMP UNIX

    if($deadline->getTimestamp() < $now)
    {
        throw new ErrorException('Deadline must be greater than current time');
    }

    // we need to get waves number
    $periodTime = $deadline->getTimestamp() - $now;
    $wavesNumber = $periodTime / $waveTime;
    $wavesNumber = $wavesNumber > 1 ? (int)(floor($wavesNumber)) : (int)(ceil($wavesNumber));

    if($proposalsNumber < $wavesNumber)
    {
        $wavesNumber = $proposalsNumber;
    }

    $proposalsByWave = $proposalsNumber / $wavesNumber;
    $proposalsByWave = $proposalsByWave > 1 ? (int)(floor($proposalsByWave)) : (int)(ceil($proposalsByWave));

    // Proposals number must be greater than proposals minimal
    if($proposalsNumberMINByWave > $proposalsByWave) {
        $proposalsByWave = $proposalsNumberMINByWave;
    }

    $counter = 1;
    $counterWave = 1;

    // TODO here possibility to add $proposals = orderProposalsByScore($proposals);

    for ($i = 1; $i <= $proposalsNumber; $i++) {
        $proposals[$i-1]->editSendAt(
            new DateTime(date('Y-m-d H:i:s', $now)),
        );

        if ($counter === $proposalsByWave) {
            $counter = 1;

            if($counterWave < $wavesNumber) {
                $now += 300;
            }

            $counterWave++;
            continue;
        }

        $counter++;
    }

    return $proposals;
}

/**
 * @param ProposalUser[] $proposals
 * @return array
 */
function orderProposalsByScore(array $proposals): array
{
    $isPermut = true;

    while ($isPermut)
    {
        $isPermut = false;

        for ($i = 0; $i < count($proposals); $i++)
        {
            if (isset($proposals[$i+1])) {
                $current = $proposals[$i];
                $next = $proposals[$i+1];

                if ($proposals[$i]->readScore() < $proposals[$i+1]->readScore())
                {
                    if(!$isPermut) {
                        $isPermut = true;
                    }

                    $proposals[$i] = $next;
                    $proposals[$i+1] = $current;

                }
            }
        }
    }

    return $proposals;
}
