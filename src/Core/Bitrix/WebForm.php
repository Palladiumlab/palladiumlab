<?php


namespace Palladiumlab\Core\Bitrix;


use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use CDBResult;
use CForm;
use CFormAnswer;
use CFormField;
use CFormResult;
use Palladiumlab\Site\User;
use Palladiumlab\Templates\Singleton;

class WebForm extends Singleton
{
    /** @var CFormResult */
    protected $formResult;
    /** @var CForm */
    protected $form;

    protected function __construct()
    {
        Loader::includeModule('form');
        $this->formResult = new CFormResult();
        $this->form = new CForm();
        parent::__construct();
    }

    /** @return WebForm */
    public static function getInstance(): Singleton
    {
        return parent::getInstance();
    }

    public function addResult(array $fields, int $webFormId, bool $sendMail = true)
    {
        $result = new Result();
        $fieldsValues = [];
        $formFields = $this->getFieldsSid($this->getDbFields($webFormId));
        foreach ($formFields as $code => $field) {
            if ($field['REQUIRED'] && empty($fields[$code])) {
                $result->addError(new Error("Отсутвует обязательное поле {$field['NAME']}"));
            } else {
                $fieldsValues[$field['SID']] = $fields[$code];
            }
        }

        if (true) { // $this->isValid($fields['MESSAGE']) todo?
            if ($result->isSuccess()) {
                $resultId = $this->formResult->Add(
                    $webFormId,
                    $fieldsValues,
                    'Y',
                    User::getInstance()->getId()
                );
                if ($resultId) {
                    if ($sendMail) {
                        $this->formResult->Mail($resultId);
                    }
                } else {
                    $result->addError(new Error(bitrix_global_app()->LAST_ERROR));
                }
            }
        } else {
            $result->addError(new Error('Получено не валидное сообщение'));
        }

        return $result;
    }

    protected function getFieldsSid(CDBResult $dbResult)
    {
        $result = [];
        while ($item = $dbResult->GetNext()) {
            $answerType = CFormAnswer::GetList($item['ID'], $by, $order, [], $isFiltered)->Fetch()['FIELD_TYPE'];
            $result[$item['COMMENTS']] = [
                'SID' => "form_{$answerType}_{$item['ID']}",
                'REQUIRED' => $item['REQUIRED'] === 'Y',
                'NAME' => $item['TITLE'],
            ];
            unset($by, $order, $isFiltered);
        }

        return $result;
    }

    protected function getDbFields(int $formId)
    {
        return (new CFormField())->GetList(
            $formId,
            'ALL',
            $by = '',
            $order = '',
            [],
            $is_filtered = false
        );
    }

    protected function isValid($text, array $validation = ['links', 'emails'])
    {
        list($linksPattern, $emailsPattern, $russianPattern) = [
            '~[a-z]+://\S+~',
            '/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i',
            '/^[а-яА-ЯЁё]+$/iu',
        ];

        list($links, $emails, $russian) = [
            preg_match_all($linksPattern, $text, $out) > 0,
            preg_match_all($emailsPattern, $text, $out) > 0,
            preg_match_all($russianPattern, $text, $out) > 0,
        ];

        foreach ($validation as $item) {
            if (${$item} === true) {
                return false;
            }
        }

        return true;
    }
}