<?php

namespace sure\base;

/**
 * Sure类中的Exception
 */
class SureException extends \Exception {

    public function __construct($message, $code = 0) {
		$this->file = basename($this->file);
        parent::__construct($message, $code);
	}

    // 自定义字符串输出的样式 */
    public function __toString() {
		return " ErrMsg:".$this->message." ; ErrFile:".$this->file." ; line:{$this->code} ";
	}
}
