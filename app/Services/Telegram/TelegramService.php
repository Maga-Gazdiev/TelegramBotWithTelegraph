<?php

namespace App\Services\Telegram;

use DefStudio\Telegraph\Handlers\WebhookHandler;
use App\Models\Chat;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use DefStudio\Telegraph\DTO\Message;
use DefStudio\Telegraph\Keyboard\ReplyButton;
use DefStudio\Telegraph\Keyboard\ReplyKeyboard;
use App\Services\Telegram\Status\DirectionByStatus;
use App\Services\Telegram\Status\FioByStatus;
use App\Services\Telegram\Status\PhoneByStatus;

class TelegramService extends WebhookHandler
{
    public TelegraphBot $bot;
    public TelegraphChat $chat;
    public Message|null $message = null;

    public function start()
    {
        $chat = $this->getChat();
        if (false) {
            //Просмотр на наличие карточки
        } else {
            if (!$chat || $chat->status !== Chat::USER_FIO_STATUS) {
                $this->initializeChatStatus();
                $this->chat->html('Привет! На связи Проверили. Рады ответить на любой твой вопрос.')->send();
                $this->chat->html('Укажите свое ФИО')->send();
            } else {
                $this->chat->html('Вы не можете выполнить эту команду.')->send();
            }
        }
    }

    public function handleChatMessage($text): void
    {
        $chat = $this->getChat();

        if ($chat && $chat->status === Chat::USER_FIO_STATUS) {
            $status = new FioByStatus($this);
            $status->process($text);
        }
        if ($chat && $chat->status === Chat::USER_PHONE_STATUS) {
            $status = new PhoneByStatus($this);
            $status->process($text);
        }
        if ($chat && $chat->status === Chat::USER_DIRECTION_STATUS) {
            $status = new DirectionByStatus($this);
            $status->process($text);
        }
    }

    public function first()
    {
        $this->initializeChatStatus();

        $value = $this->data->get('value');

        if ($value == "Да" || $value == "Нет") {
            $status = new DirectionByStatus($this);
            $status->process($value);
        } else {
            $this->chat->html('Мы не смогли распознать Ваш ответ, давайте попробуем еще раз?')->send();
        }
    }

    public function initializeChatStatus()
    {
        $chatId = $this->chat->chat_id;
        Chat::updateOrCreate(['chat_id' => $chatId], ['status' => Chat::USER_FIO_STATUS]);
    }

    public function getChat()
    {
        $chatId = $this->chat->chat_id;
        return Chat::where('chat_id', $chatId)->first();
    }

    public function requestPhoneNumber()
    {
        $chat = $this->getChat();
        $chat->update(["status" => Chat::USER_PHONE_STATUS]);

        $this->chat->message("Уточните ваш номер телефона. Формат номера +79990001122")->replyKeyboard(ReplyKeyboard::make()->oneTime()->buttons([
            ReplyButton::make('Отправить свой номер телефона')->requestContact(),
        ]))->send();
    }
} 
