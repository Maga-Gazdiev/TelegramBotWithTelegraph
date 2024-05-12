<?php

namespace App\Services\Telegram\Status;

use App\Services\Telegram\TelegramService;
use App\Interfaces\StatusInterface;

class DirectionByStatus implements StatusInterface
{
    protected $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    public function process($text): void
    {
        $chat = $this->telegramService->getChat();
        if ($text == "Да") {
            $this->telegramService->chat->html('Тут уже идет работа с 1C и AMO')->send();
            $chat->update(["status" => "userAsksQuestion"]);
        } elseif ($text == "Нет") {
            $this->telegramService->requestPhoneNumber();
        } else {
            $this->telegramService->chat->html('Мы не смогли распознать ваш ответ, попробуйте еще раз')->send();
        }
    }
}