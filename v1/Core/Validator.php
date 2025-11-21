<?php
namespace BO_System\Core;

/**
 * Lớp Validator dùng để kiểm tra dữ liệu đầu vào.
 * Hỗ trợ các quy tắc kiểm tra cơ bản như required, email, max length.
 */
class Validator
{
    private array $errors = [];
    private array $data;

    private function __construct(array $data) {
        $this->data = $data;
    }

    /**
     * Tạo một instance Validator mới và thực hiện kiểm tra.
     *
     * @param array $data Dữ liệu cần kiểm tra (thường là $_POST).
     * @param array $rules Mảng các quy tắc kiểm tra (ví dụ: ['username' => 'required|max:50']).
     * @return self
     */
    public static function make(array $data, array $rules): self
    {
        $validator = new self($data);
        foreach ($rules as $field => $ruleString) {
            $rulesArray = explode('|', $ruleString);
            foreach ($rulesArray as $rule) {
                $value = $data[$field] ?? null;
                $validator->applyRule($field, $value, $rule);
            }
        }
        return $validator;
    }

    /**
     * Áp dụng một quy tắc kiểm tra cụ thể cho một trường.
     *
     * @param string $field Tên trường.
     * @param mixed $value Giá trị của trường.
     * @param string $rule Tên quy tắc (có thể kèm tham số, ví dụ: max:255).
     */
    private function applyRule(string $field, $value, string $rule): void
    {
        $params = [];
        if (strpos($rule, ':')) {
            [$rule, $paramStr] = explode(':', $rule, 2);
            $params = explode(',', $paramStr);
        }

        $methodName = 'validate' . ucfirst($rule);
        if (method_exists($this, $methodName)) {
            $this->{$methodName}($field, $value, $params);
        }
    }

    /**
     * Kiểm tra xem có lỗi validation nào không.
     *
     * @return bool True nếu có lỗi, False nếu không.
     */
    public function fails(): bool {
        return !empty($this->errors);
    }

    /**
     * Lấy danh sách các lỗi validation.
     *
     * @return array Mảng các lỗi.
     */
    public function getErrors(): array {
        return $this->errors;
    }

    // --- Các quy tắc validation ---

    /**
     * Kiểm tra trường bắt buộc.
     */
    private function validateRequired(string $field, $value): void {
        if (empty($value)) {
            $this->errors[$field][] = "Trường này là bắt buộc.";
        }
    }

    /**
     * Kiểm tra định dạng email.
     */
    private function validateEmail(string $field, $value): void {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = "Trường này phải là một địa chỉ email hợp lệ.";
        }
    }

    /**
     * Kiểm tra độ dài tối đa của chuỗi.
     */
    private function validateMax(string $field, $value, array $params): void {
        $maxLength = (int)($params[0] ?? 255);
        if (mb_strlen($value) > $maxLength) {
            $this->errors[$field][] = "Trường này không được vượt quá {$maxLength} ký tự.";
        }
    }

    /**
     * Kiểm tra độ dài tối thiểu của chuỗi.
     */
    private function validateMin(string $field, $value, array $params): void {
        $minLength = (int)($params[0] ?? 0);
        if (mb_strlen($value) < $minLength) {
            $this->errors[$field][] = "Trường này phải có ít nhất {$minLength} ký tự.";
        }
    }
}