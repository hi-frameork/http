<?php declare(strict_types=1);

namespace Hi\Http\Message;

use Hi\Helpers\Arr;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * 通过 HTTP 请求上传的一个文件内容。
 *
 * 此接口的实例是被视为无法修改的，所有能修改状态的方法，都 **必须** 有一套机制，在内部保
 * 持好原有的内容，然后把修改状态后的，新的实例返回。
 */
class UploadedFile implements UploadedFileInterface
{
    /**
     * @var boool
     */
    protected $alreadyMoved = false;

    /**
     * @var string|null
     */
    protected $clientFilename;

    /**
     * @var string|null
     */
    protected $clientMediaType;

    /**
     * @var int
     */
    protected $error = 0;

    /**
     * @var string
     */
    protected $fileName = '';

    /**
     * @var int|null
     */
    protected $size;

    /**
     * @var StreamInterface|string|null
     */
    protected $stream;

    /**
     * UploadedFile constructor.
     *
     * @param StreamInterface|string|null $stream
     * @param int|null                    $size
     * @param int                         $error
     * @param string|null                 $clientFilename
     * @param string|null                 $clientMediaType
     */
    public function __construct(
        $stream,
        int $size = null,
        int $error = 0,
        string $clientFilename = null,
        string $clientMediaType = null
    ) {
        /**
         * Check the stream passed. It can be a string representing a file or
         * a StreamInterface
         */
        $this->checkStream($stream, $error);

        /**
         * Check the error
         */
        $this->checkError($error);

        $this->size            = $size;
        $this->clientFilename  = $clientFilename;
        $this->clientMediaType = $clientMediaType;
    }

    /**
     * 获取上传文件的数据流。
     *
     * 此方法必须返回一个 `StreamInterface` 实例，此方法的目的在于允许 PHP 对获取到的数
     * 据流直接操作，如 stream_copy_to_stream() 。
     *
     * 如果在调用此方法之前调用了 `moveTo()` 方法，此方法 **必须** 抛出异常。
     *
     * @return StreamInterface 上传文件的数据流
     * @throws \RuntimeException 没有数据流的情形下。
     * @throws \RuntimeException 无法创建数据流。
     */
    public function getStream()
    {
        if (0 !== $this->error) {
            throw new InvalidArgumentException(
                $this->getErrorDescription($this->error)
            );
        }

        if ($this->alreadyMoved) {
            throw new InvalidArgumentException(
                "The file has already been moved to the target location"
            );
        }

        if (! $this->stream instanceof StreamInterface) {
            $this->stream = new Stream($this->fileName);
        }

        return $this->stream;
    }

    /**
     * 把上传的文件移动到新目录。
     *
     * 此方法保证能同时在 `SAPI` 和 `non-SAPI` 环境下使用。实现类库 **必须** 判断
     * 当前处在什么环境下，并且使用合适的方法来处理，如 move_uploaded_file(), rename()
     * 或者数据流操作。
     *
     * $targetPath 可以是相对路径，也可以是绝对路径，使用 rename() 解析起来应该是一样的。
     *
     * 当这一次完成后，原来的文件 **必须** 会被移除。
     * 
     * 如果此方法被调用多次，一次以后的其他调用，都要抛出异常。
     *
     * 如果在 SAPI 环境下的话，$_FILES 内有值，当使用  moveTo(), is_uploaded_file()
     * 和 move_uploaded_file() 方法来移动文件时 **应该** 确保权限和上传状态的准确性。
     * 
     * 如果你希望操作数据流的话，请使用 `getStream()` 方法，因为在 SAPI 场景下，无法
     * 保证书写入数据流目标。
     * 
     * @see http://php.net/is_uploaded_file
     * @see http://php.net/move_uploaded_file
     * @param string $targetPath 目标文件路径。
     * @throws \InvalidArgumentException 参数有问题时抛出异常。
     * @throws \RuntimeException 发生任何错误，都抛出此异常。
     * @throws \RuntimeException 多次运行，也抛出此异常。
     */
    public function moveTo($targetPath)
    {
        if ($this->alreadyMoved) {
            throw new InvalidArgumentException("File has already been moved");
        }

        if (0 !== $this->error) {
            throw new InvalidArgumentException(
                $this->getErrorDescription($this->error)
            );
        }

        /**
         * All together for early failure
         */
        if (! (is_string($targetPath) && !empty($targetPath) && is_dir(dirname($targetPath)) && is_writable(dirname($targetPath)))) {
            throw new InvalidArgumentException(
                "Target folder is empty string, not a folder or not writable"
            );
        }

        $sapi = constant("PHP_SAPI");

        if (empty($sapi) || !empty($this->fileName) || strpos($sapi, 'cli') === 1 || strpos($sapi, "phpdbg") === 1) {
            $this->storeFile($targetPath);
        } else {
            if (true !== move_uploaded_file($this->fileName, $targetPath)) {
                throw new InvalidArgumentException(
                    "The file cannot be moved to the target folder"
                );
            }
        }

        $this->alreadyMoved = true;
    }

    /**
     * 获取文件大小。
     *
     * 实现类库 **应该** 优先使用 $_FILES 里的 `size` 数值。
     * 
     * @return int|null 以 bytes 为单位，或者 null 未知的情况下。
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * 获取上传文件时出现的错误。
     *
     * 返回值 **必须** 是 PHP 的 UPLOAD_ERR_XXX 常量。
     *
     * 如果文件上传成功，此方法 **必须** 返回 UPLOAD_ERR_OK。
     *
     * 实现类库 **必须** 返回 $_FILES 数组中的 `error` 值。
     * 
     * @see http://php.net/manual/en/features.file-upload.errors.php
     * @return int PHP 的 UPLOAD_ERR_XXX 常量。
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 获取客户端上传的文件的名称。
     * 
     * 永远不要信任此方法返回的数据，客户端有可能发送了一个恶意的文件名来攻击你的程序。
     * 
     * 实现类库 **应该** 返回存储在 $_FILES 数组中 `name` 的值。
     *
     * @return string|null 用户上传的名字，或者 null 如果没有此值。
     */
    public function getClientFilename()
    {
        return $this->clientFilename;
    }

    /**
     * 客户端提交的文件类型。
     * 
     * 永远不要信任此方法返回的数据，客户端有可能发送了一个恶意的文件类型名称来攻击你的程序。
     *
     * 实现类库 **应该** 返回存储在 $_FILES 数组中 `type` 的值。
     *
     * @return string|null 用户上传的类型，或者 null 如果没有此值。
     */
    public function getClientMediaType()
    {
        return $this->clientMediaType;
    }

    /**
     * Checks the passed error code and if not in the range throws an exception
     *
     * @param StreamInterface|resource|string $stream
     * @param int                             $error
     */
    private function checkStream($stream, int $error): void
    {
        if (0 === $error) {
            switch (true) {
                case (is_string($stream)):
                    $this->fileName = $stream;
                    break;
                case (is_resource($stream)):
                    $this->stream = new Stream($stream);
                    break;
                case ($stream instanceof StreamInterface):
                    $this->stream = $stream;
                    break;
                default:
                    throw new InvalidArgumentException("文件或 stream 无效");
            }
        }
    }

    /**
     * Checks the passed error code and if not in the range throws an exception
     *
     * @param int $error
     */
    private function checkError(int $error): void
    {
        if ($error < 0 || $error > 8) {
            throw new InvalidArgumentException(
                "Invalid error. Must be one of the UPLOAD_ERR_* constants"
            );
        }

        $this->error = $error;
    }

    /**
     * Returns a description string depending on the upload error code passed
     *
     * @param int $error
     *
     * @return string
     */
    private function getErrorDescription(int $error): string
    {
        $errors = [
            0 => "There is no error, the file uploaded with success.",
            1 => "The uploaded file exceeds the upload_max_filesize directive in php.ini.",
            2 => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.",
            3 => "The uploaded file was only partially uploaded.",
            4 => "No file was uploaded.",
            6 => "Missing a temporary folder.",
            7 => "Failed to write file to disk.",
            8 => "A PHP extension stopped the file upload."
        ];

        return Arr::get($errors, $error, "Unknown upload error");
    }

    /**
     * Store a file in the new location (stream)
     *
     * @param string $targetPath
     */
    private function storeFile(string $targetPath): void
    {
        $handle = fopen($targetPath, "w+b");
        if (false === $handle) {
            throw new InvalidArgumentException("Cannot write to file.");
        }

        $stream = $this->getStream();

        $stream->rewind();

        while (true !== $stream->eof()) {
            $data = $stream->read(2048);

            fwrite($handle, $data);
        }

        fclose($handle);
    }
}
