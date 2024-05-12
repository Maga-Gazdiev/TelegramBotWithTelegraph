<?php

namespace App\Services\Telegram\Status;

use App\Services\Telegram\TelegramService;
use App\Models\Chat;
use App\Models\User;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use App\Interfaces\StatusInterface;

class FioByStatus implements StatusInterface
{
    protected $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    public function process($text): void
    {
        $chat = $this->telegramService->getChat();
        $users = User::where('name', $text)->get();

        if ($users->isEmpty()) {
            $this->telegramService->chat->html('Пользователь с таким ФИО был не найден.')->send();
            $this->telegramService->requestPhoneNumber();
            $chat->update(["status" => "getUserByPhone"]);
        } elseif ($users->count() === 1) {
            $chat->update(['message' => $text, "status" => "getUserByDirection"]);
            $this->askUserDirection($users->first());
        } elseif ($users->count() > 1) {
            $this->telegramService->requestPhoneNumber();
            $chat->update(['message' => $text, "status" => "getUserByPhone"]);
        }

        $chat->update(['message' => $text]);
    }
    protected function askUserDirection($user)
    {
        $this->telegramService->chat->message("{$user->name}, вы обучаетесь на направлении  в ?")
            ->keyboard(
                Keyboard::make()->buttons([
                    Button::make("Да")->action("first")->param('value', 'Да'),
                    Button::make("Нет")->action("first")->param('value', 'Нет'),
                ])
            )->send();
    }
}
