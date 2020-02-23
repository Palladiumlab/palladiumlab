<?php


namespace Palladiumlab\Site;


use Bitrix\Main\UserTable;
use CUser;
use Exception;
use Palladiumlab\Templates\Singleton;
use Palladiumlab\Traits\StaticCacheTrait;

class User extends Singleton
{
    use StaticCacheTrait;

    protected static $instances = [];
    /** @var CUser */
    protected $user;
    protected $fields = [];
    protected $id;

    protected function __construct(int $id = 0)
    {
        global $USER;
        $this->user = $USER;
        if ($id <= 0 && $USER->IsAuthorized()) {
            $id = (int)$USER->GetID();
        }
        if (self::isUserExist($id)) {
            $this->fields = UserTable::getRow([
                'select' => ['*'],
                'filter' => ['=ID' => $USER->GetID()],
                'cache' => ['ttl' => 60 * 60],
            ]) ?: [];
        }
        $this->id = $id;

        parent::__construct();
    }

    protected static function isUserExist(int $userId)
    {
        try {
            return UserTable::getCount([
                    '=ID' => $userId
                ]) > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param int $id
     * @return User
     */
    public static function getInstance(int $id = 0): Singleton
    {
        if ($id > 0 && static::isUserExist($id)) {
            if (!isset(static::$instances[$id])) {
                static::$instances[$id] = new static;
            }

            return static::$instances[$id];
        } else {
            return parent::getInstance();
        }
    }

    public function isAuthorized()
    {
        return $this->user->IsAuthorized();
    }

    public function getId()
    {
        return $this->id > 0 ? $this->id : 0;
    }

    public function getCurrent()
    {
        return $this->user;
    }

    public function getField(string $field, $default = null, int $cache = 60 * 60)
    {
        if ($this->id > 0) {
            try {
                if (($fromStatic = $this->getStatic($field, false)) && $cache > 0) {
                    return $fromStatic;
                } else {
                    $fromFields = $this->fields[$field];
                    if ($fromFields) {
                        return $fromFields;
                    }
                    $fromTable = UserTable::getRow([
                        'select' => [$field],
                        'filter' => ['=ID' => $this->id],
                        'cache' => ['ttl' => $cache],
                    ])[$field];
                    $this->fields[$field] = $fromTable;
                    if ($fromTable && $cache > 0) {
                        $this->setStatic($field, $fromStatic);
                    }
                    return $fromTable ?: $default;
                }
            } catch (Exception $e) {
                return $default;
            }
        }
        return $default;
    }
}