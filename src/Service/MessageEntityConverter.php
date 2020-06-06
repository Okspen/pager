<?php

declare(strict_types=1);

namespace Pager\Service;

use TelegramBot\Api\Types\MessageEntity;

class MessageEntityConverter
{
    public static function convertToHtml(string $rawText, ?array $entities): string
    {
        $entities = $entities ?? [];

        $searchAndReplace = [];

        foreach ($entities as $entity) {
            /** @var MessageEntity $entity */
            $search = mb_substr($rawText, $entity->getOffset(), $entity->getLength());

            switch ($entity->getType()) {
                case 'bold':
                    $replace = sprintf('<b>%s</b>', $search);

                    break;
                case 'italic':
                    $replace = sprintf('<i>%s</i>', $search);

                    break;
                default:
                    $replace = $search;

                    break;
            }

            $searchAndReplace[] = [
                'search' => $search,
                'replace' => $replace,
            ];
        }

        $newText = $rawText;

        foreach ($searchAndReplace as $item) {
            $newText = str_replace($item['search'], $item['replace'], $newText);
        }

        return $newText;
    }
}
