<?php
namespace App\Core;

/**
 * Small rule-based validator. Rules are pipe separated, e.g.
 *   'email' => 'required|email|max:190'
 */
final class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function make(array $data, array $rules, array $labels = []): self
    {
        $v = new self($data);
        foreach ($rules as $field => $ruleset) {
            $v->apply($field, $ruleset, $labels[$field] ?? self::humanise($field));
        }
        return $v;
    }

    private function apply(string $field, string $ruleset, string $label): void
    {
        $value = $this->data[$field] ?? null;
        $value = is_string($value) ? trim($value) : $value;

        foreach (explode('|', $ruleset) as $rule) {
            [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);

            $blank = $value === null || $value === '';
            if ($blank && $name !== 'required') {
                continue; // optional fields only validate when filled in
            }

            switch ($name) {
                case 'required':
                    if ($blank) {
                        $this->fail($field, "$label is required.");
                    }
                    break;
                case 'email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $this->fail($field, "Enter a valid email address.");
                    }
                    break;
                case 'min':
                    if (mb_strlen((string) $value) < (int) $arg) {
                        $this->fail($field, "$label must be at least $arg characters.");
                    }
                    break;
                case 'max':
                    if (mb_strlen((string) $value) > (int) $arg) {
                        $this->fail($field, "$label must be $arg characters or fewer.");
                    }
                    break;
                case 'in':
                    if (!in_array((string) $value, explode(',', (string) $arg), true)) {
                        $this->fail($field, "$label is not a valid choice.");
                    }
                    break;
                case 'date':
                    if (!strtotime((string) $value)) {
                        $this->fail($field, "$label must be a valid date.");
                    }
                    break;
                case 'numeric':
                    if (!is_numeric($value)) {
                        $this->fail($field, "$label must be a number.");
                    }
                    break;
                case 'url':
                    if (!filter_var($value, FILTER_VALIDATE_URL)) {
                        $this->fail($field, "$label must be a valid URL.");
                    }
                    break;
                case 'matches':
                    if ((string) $value !== (string) ($this->data[$arg] ?? '')) {
                        $this->fail($field, "$label does not match.");
                    }
                    break;
                case 'username':
                    if (!preg_match('/^[a-z0-9._]{3,40}$/i', (string) $value)) {
                        $this->fail($field, "$label may only use letters, numbers, dots and underscores.");
                    }
                    break;
            }
        }
    }

    public function fail(string $field, string $message): void
    {
        $this->errors[$field] ??= $message;
    }

    public function passes(): bool  { return $this->errors === []; }
    public function fails(): bool   { return $this->errors !== []; }
    public function errors(): array { return $this->errors; }
    public function firstError(): string { return (string) (reset($this->errors) ?: ''); }

    private static function humanise(string $field): string
    {
        return ucfirst(str_replace('_', ' ', $field));
    }
}
