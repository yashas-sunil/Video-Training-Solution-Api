<?php
namespace App\Exceptions;


use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidOtpException extends HttpException
{

    /**
     * @param string $message The internal exception message
     * @param \Exception $previous The previous exception
     * @param int $code The internal exception code
     * @param array $headers
     */
    public function __construct($message = null, \Exception $previous = null, $code = 0, array $headers = [])
    {
        parent::__construct(400, $message, $previous, $headers, $code);
    }

    /**
     * Returns the status code.
     *
     * @return int An HTTP response status code
     */
    public function getStatusCode()
    {
        return parent::getStatusCode();
    }

    /**
     * Returns response headers.
     *
     * @return array Response headers
     */
    public function getHeaders()
    {
        return parent::getHeaders();
    }
}