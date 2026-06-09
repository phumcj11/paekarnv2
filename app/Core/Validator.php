<?php
namespace App\Core;

class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data) { $this->data = $data; }

    public static function make(array $data): self { return new self($data); }

    /**
     * $rules = ['email' => 'required|email|max:160']
     */
    public function validate(array $rules, array $messages = []): bool
    {
        foreach ($rules as $field => $ruleStr) {
            $value = $this->data[$field] ?? null;
            foreach (explode('|', $ruleStr) as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $ok = match ($name) {
                    'required' => !($value === null || $value === '' || (is_array($value) && empty($value))),
                    'email'    => !$value || filter_var($value, FILTER_VALIDATE_EMAIL),
                    'min'      => !$value || mb_strlen((string)$value) >= (int)$param,
                    'max'      => !$value || mb_strlen((string)$value) <= (int)$param,
                    'numeric'  => !$value || is_numeric($value),
                    'integer'  => !$value || filter_var($value, FILTER_VALIDATE_INT) !== false,
                    'phone'    => !$value || preg_match('/^[0-9\-+\s()]{8,20}$/', (string)$value),
                    'date'     => !$value || strtotime((string)$value) !== false,
                    'in'       => !$value || in_array($value, explode(',', (string)$param), true),
                    'same'     => $value === ($this->data[$param] ?? null),
                    default    => true,
                };
                if (!$ok) {
                    $key = "$field.$name";
                    $this->errors[$field] = $messages[$key]
                        ?? $messages[$field]
                        ?? self::defaultMessage($field, $name, $param);
                    break;
                }
            }
        }
        return empty($this->errors);
    }

    public function errors(): array { return $this->errors; }
    public function first(string $field): ?string { return $this->errors[$field] ?? null; }

    private static function defaultMessage(string $field, string $rule, $param): string
    {
        return match ($rule) {
            'required' => "กรุณากรอก $field",
            'email'    => "$field ต้องเป็นอีเมลที่ถูกต้อง",
            'min'      => "$field ต้องมีอย่างน้อย $param ตัวอักษร",
            'max'      => "$field ต้องไม่เกิน $param ตัวอักษร",
            'numeric'  => "$field ต้องเป็นตัวเลข",
            'integer'  => "$field ต้องเป็นจำนวนเต็ม",
            'phone'    => "$field ต้องเป็นเบอร์โทรศัพท์ที่ถูกต้อง",
            'date'     => "$field ต้องเป็นวันที่ที่ถูกต้อง",
            'same'     => "$field ไม่ตรงกัน",
            default    => "$field ไม่ถูกต้อง",
        };
    }
}
