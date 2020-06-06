<?php

declare(strict_types=1);

namespace Pager\Service\Bot\Service;

class RetryCaller
{
    /**
     * @return mixed|null
     *
     * @throws \TelegramBot\Api\HttpException
     */
    public static function call(\Closure $closure, int $retryCount = 3)
    {
        $triesLeft = $retryCount;

        $result = null;

        while ($triesLeft > 0) {
            try {
                $result = $closure();
                $triesLeft = 0;
            } catch (\TelegramBot\Api\HttpException $e) {
                if (false === mb_stristr($e->getMessage(), 'Operation timed out after')) {
                    throw $e;
                }

                --$triesLeft;
                if (0 === $triesLeft) {
                    throw $e;
                }
            }
        }

        return $result;
    }
}
