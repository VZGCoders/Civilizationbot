<?php


/*
 * This file is a part of the Civ13 project.
 *
 * Copyright (c) 2024-present Valithor Obsidion <valithor@valzargaming.com>
 */

namespace Civ13;

use Byond\Byond;
use Carbon\Carbon;

/*
 * Class representing a ban
 * 
 * Ban logs are formatted by the following:
 *   0 => Type
 *   1 => Job (defaults to nil)
 *   2 => UID
 *   3 => Reason
 *   4 => Admin
 *   5 => Date
 *   6 => Timestamp?
 *   7 => Expires
 *   8 => Ckey
 *   9 => CID
 *   10 => IP
 * Example log: // Server;nil;123456789;advertising;valithor;Tue Jul 28 23.28.32 2020;38028869632;Expires in 100 years;ckey;000000001;123.45.67.890|||
 */
class Ban
{
    public ?string $type = 'Server';
    public ?string $job = 'nil';
    private ?string $uid = null;
    public ?string $reason;
    public ?string $admin;
    private ?string $date = null;
    private ?string $timestamp = null;
    public ?string $expires = 'Expires in 999 years';
    public ?string $ckey;
    public ?string $cid = '0';
    public ?string $ip = '0';

    public function __construct(string $ban)
    {
        $array = explode(';', $ban);
        if (count($array) !== 11) throw new \Exception('Invalid ban log format');
        /** @var ?string $field */
        foreach (array_keys(get_class_vars(self::class)) as $index => $field)
            $this->$field = $array[$index] === 'nil'
                ? (new \ReflectionProperty($this, $field))->getDefaultValue()
                : $array[$index];
    }

    public function uid()
    {
        return $this->uid ?? self::num2text(rand(1, 1000*1000*1000), 20);
    }
    
    public function date(): Carbon
    { // Sun Oct 13 10.05.32 2024
        return $this->date ?? Carbon::createFromFormat('D M d H.i.s Y', date('D M d H.i.s Y'));
    }

    public function timestamp(): string
    {
        return $this->timestamp ?? strval(Byond::convertToByondFromUnix(time()));
    }

    /**
     * Converts a number to its textual representation.
     * @link https://secure.byond.com/docs/ref/#/proc/num2text
     * 
     * @param mixed $N The number to be converted.
     * @param int $SigFig The number of significant figures to include in the output (default is 6).
     * @param int|null $Digits The number of digits to pad the result to (only used if $Radix is not 10).
     * @param int $Radix The base to convert the number to (default is 10).
     * @param bool $Scientific Whether to use scientific notation (default is false, but Byond produces scientific notation when there are more than 6 digits).
     * @return string The textual representation of the number.
     */
    public static function num2text($N, int $SigFig = 6, ?int $Digits = null, int $Radix = 10, bool $Scientific = false): string
    {
        if (! is_numeric($N)) throw new \Exception('Invalid number');

        if ($Radix !== 10) {
            $result = base_convert(intval($N), 10, $Radix);
            if ($Digits !== null) {
                $result = str_pad($result, $Digits, '0', STR_PAD_LEFT);
            }
            return $result;
        }

        if ($Scientific && $SigFig > 6) {
            if ($SigFig !== null) {
                $format = sprintf('%%.%de', $SigFig - 1);
                $result = sprintf($format, $N);
                if (strpos($result, 'e') !== false) {
                    list($base, $exp) = explode('e', $result);
                    $base = rtrim(rtrim($base, '0'), '.');
                    $result = $base . 'e' . $exp;
                }
                return $result;
            }
        }
        else {
            if ($SigFig !== null) {
                $format = sprintf('%%.%df', $SigFig - 1);
                $result = sprintf($format, $N);
                return rtrim(rtrim($result, '0'), '.');
            }
        }

        return strval($N);
    }

    public function __toArray()
    {
        return [
            'type' => $this->type ?? 'Server',
            'job' => $this->job ?? 'nil',
            'uid' => $this->uid,
            'reason' => $this->reason,
            'admin' => $this->admin,
            'date' => $this->date ?? self::date(),
            'timestamp' => $this->timestamp ?? self::timestamp(),
            'expires' => $this->expires,
            'ckey' => $this->ckey,
            'cid' => $this->cid ?? '0',
            'ip' => $this->ip ?? '0'
        ];
    }

    public function __toString(): string
    {
        return
            $this->type ?? 'Server' . ';'.
            $this->job ?? 'nil' . ';'.
            $this->uid . ';'.
            $this->reason . ';'.
            $this->admin . ';'.
            $this->date ?? self::date() . ';'.
            $this->timestamp ?? self::timestamp() . ';'.
            $this->expires . ';' .
            $this->ckey . ';' .
            $this->cid ?? '0' . ';' .
            $this->ip ?? '0' . '|||';
    }

    public function __get($name)
    {
        if (method_exists($this, $name)) return $this->$name();
        return $this->$name;
    }

    public function __debugInfo()
    {
        return $this->__toArray();
    }
}

$ban = new Ban('nil;nil;nil;advertising;valithor;nil;nil;Expires in 100 years;ckey;000000001;123.45.67.890|||');
var_dump($ban);