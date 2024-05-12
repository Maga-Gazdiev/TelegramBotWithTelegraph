<?php

namespace App\Services\Telegram\Status;

use App\Services\Telegram\TelegramService;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use App\Interfaces\StatusInterface;

class PhoneByStatus implements StatusInterface
{
    protected $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    public function process($text): void
    {
        if ($this->telegramService->message && $this->telegramService->message->contact() && $this->telegramService->message->contact()->phoneNumber()) {
            $phoneNumber = $this->formatPhoneNumber($this->telegramService->message->contact()->phoneNumber());
        } elseif ($text) {
            $phoneNumber = $this->formatPhoneNumber($text);
        }

        if ($this->isValidPhoneNumber($phoneNumber)) {
            $this->processValidPhoneNumber($phoneNumber);
        } else {
            $this->telegramService->chat->html('Указанный номер телефона некорректный. Давайте попробуем еще раз')->send();
        }
    }

    protected function formatPhoneNumber($phoneNumber, $defaultRegion = 'RU')
    {
        $phoneUtil = PhoneNumberUtil::getInstance();
        $numberProto = $phoneUtil->parse($phoneNumber, $defaultRegion);
        return $phoneUtil->format($numberProto, PhoneNumberFormat::NATIONAL);
    }

    protected function isValidPhoneNumber($text)
    {
        $phoneNumber = preg_replace('/\D/', '', $text);

        return preg_match("/^\+7\s?\d{3}\s?\d{3}\s?\d{4}$/", $text) || preg_match("/^\+7\d{10}$/", $text) || preg_match("/^8\d{10}$/", $phoneNumber);
    }


    protected function processValidPhoneNumber($text)
    {
        $this->telegramService->chat->html('Мы получили Ваш номер телефона, теперь думаем, что с ним делать')->send();
        $this->telegramService->chat->html("Отформатированный номер: " . $text)->send();
        $this->telegramService->chat->html('Проверка по специальности полученной из 1С')->send();
    }
}
