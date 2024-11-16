<?php

namespace App\Exceptions;

use App\Http\Response\ApiResponse;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class CustomException extends Exception
{
    protected $statusCode;
    protected $message;
    protected $logMessage;

    /**
     * Constructor nhận thông báo lỗi, mã trạng thái và log message.
     *
     * @param string $message
     * @param int $statusCode
     * @param string|null $logMessage
     */
    public function __construct($message = 'Lỗi không xác định', $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR, $logMessage = null)
    {
        $this->message = $message;
        $this->statusCode = $statusCode;
        $this->logMessage = $logMessage; 

        parent::__construct($this->message);
    }

    public function getLogMessage()
    {
        return $this->logMessage;
    }

    /**
     * Xử lý lỗi và trả về phản hồi JSON.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function report()
    {
        // Trả về phản hồi lỗi JSON
        return ApiResponse::error($this->message, $this->statusCode);
    }
}
