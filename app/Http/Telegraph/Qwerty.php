<?php

namespace App\Http\Telegraph;

use DefStudio\Telegraph\Handlers\WebhookHandler;
use App\Models\Chat;
use App\Models\User;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Keyboard\ReplyButton;
use DefStudio\Telegraph\Keyboard\ReplyKeyboard;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;

class Qwerty extends WebhookHandler
{
    public function start()
    {
        $chat = $this->getChat();
        if (false) {
            //Просмотр на наличие карточки
        } else {
            if (!$chat || $chat->status === 'getUserByFIO') {
                Chat::updateOrCreate(['chat_id' => $this->chat->chat_id], ['status' => 'getUserByFIO']);

                $this->chat->html('Привет! На связи Проверили. Рады ответить на любой твой вопрос.')->send();
                $this->chat->html('Укажите свое ФИО')->send();
            } else {
                $this->chat->html('Вы не можете выполнить эту команду.')->send();
            }
        }
    }


    protected function handleChatMessage($text): void
    {
        $chat = $this->getChat();

        if ($chat && $chat->status === Chat::USER_FIO_STATUS) {
            $this->processUserByFIO($text);
        }
        if ($chat && $chat->status === Chat::USER_PHONE_STATUS) {
            $this->processUserByPhone($text);
        }
        if ($chat && $chat->status === Chat::USER_DIRECTION_STATUS) {
            $this->processUserByDirection($text);
        }
    }

    public function first()
    {
        $this->initializeChat();

        $value = $this->data->get('value');

        if ($value == "Да" || $value == "Нет") {
            $this->processUserByDirection($value);
        } else {
            $this->chat->html('Мы не смогли распознать Ваш ответ, давайте попробуем еще раз?')->send();
        }
    }

    protected function initializeChat()
    {
        $chatId = $this->chat->chat_id;
        Chat::updateOrCreate(['chat_id' => $chatId], ['status' => 'getUserByFIO']);
    }

    protected function getChat()
    {
        $chatId = $this->chat->chat_id;
        return Chat::where('chat_id', $chatId)->first();
    }

    protected function processUserByFIO($text)
    {
        $chat = $this->getChat();
        $users = User::where('name', $text)->get();

        if ($users->isEmpty()) {
            $this->chat->html('Пользователь с таким ФИО был не найден.')->send();
            $this->requestPhoneNumber();
            $chat->update(["status" => "getUserByPhone"]);
        } elseif ($users->count() === 1) {
            $chat->update(['message' => $text, "status" => "getUserByDirection"]);
            $this->askUserDirection($users->first());
        } elseif ($users->count() > 1) {
            $this->requestPhoneNumber();
            $chat->update(['message' => $text, "status" => "getUserByPhone"]);
        }

        $chat->update(['message' => $text]);
    }


    protected function processUserByPhone($text)
    {

        if ($this->message && $this->message->contact() && $this->message->contact()->phoneNumber()) {
            $phoneNumber = $this->formatPhoneNumber($this->message->contact()->phoneNumber());
        } elseif ($text) {
            $phoneNumber = $this->formatPhoneNumber($text);
        }

        if ($this->isValidPhoneNumber($phoneNumber)) {
            $this->processValidPhoneNumber($phoneNumber);
        } else {
            $this->chat->html('Указанный номер телефона некорректный. Давайте попробуем еще раз')->send();
        }
    }


    protected function processUserByDirection($text)
    {
        if ($text == "Да") {
            $this->chat->html('Тут уже идет работа с 1C и AMO')->send();
        } elseif ($text == "Нет") {
            $this->requestPhoneNumber();
        } else {
            $this->chat->html('Мы не смогли распознать ваш ответ, попробуйте еще раз')->send();
        }
    }

    protected function askUserDirection($user)
    {
        $this->chat->message("{$user->name}, вы обучаетесь на направлении  в ?")
            ->keyboard(
                Keyboard::make()->buttons([
                    Button::make("Да")->action("first")->param('value', 'Да'),
                    Button::make("Нет")->action("first")->param('value', 'Нет'),
                ])
            )->send();
    }

    protected function requestPhoneNumber()
    {
        $chat = $this->getChat();
        $chat->update(["status" => "getUserByPhone"]);

        $this->chat->message("Уточните ваш номер телефона. Формат номера +79990001122")->replyKeyboard(ReplyKeyboard::make()->oneTime()->buttons([
            ReplyButton::make('Отправить свой номер телефона')->requestContact(),
        ]))->send();
    }

    protected function isValidPhoneNumber($text)
    {
        $phoneNumber = preg_replace('/\D/', '', $text);

        return preg_match("/^\+7\s?\d{3}\s?\d{3}\s?\d{4}$/", $text) || preg_match("/^\+7\d{10}$/", $text) || preg_match("/^8\d{10}$/", $phoneNumber);
    }


    protected function processValidPhoneNumber($text)
    {
        $this->chat->html('Мы получили Ваш номер телефона, теперь думаем, что с ним делать')->send();
        $this->chat->html("Отформатированный номер: " . $text)->send();
        $this->chat->html('Проверка по специальности полученной из 1С')->send();
    }

    protected function formatPhoneNumber($phoneNumber, $defaultRegion = 'RU')
    {
        $phoneUtil = PhoneNumberUtil::getInstance();
        $numberProto = $phoneUtil->parse($phoneNumber, $defaultRegion);
        return $phoneUtil->format($numberProto, PhoneNumberFormat::NATIONAL);
    }
}
