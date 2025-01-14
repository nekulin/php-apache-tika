<?php

namespace Vaites\ApacheTika\Metadata;

use Exception;

/**
 * Standarized metadata class with common attributes for all document types.
 *
 * @author  David Martínez <contacto@davidmartinez.net>
 */
abstract class Metadata
{
    /**
     * MIME type.
     *
     * @var string
     */
    public $mime = null;

    /**
     * Date created.
     *
     * @var \DateTime
     */
    public $created = null;

    /**
     * Date updated or last modified.
     *
     * @var \DateTime
     */
    public $updated = null;

    /**
     * RAW attributes returned by Apache Tika.
     *
     * @var array
     */
    public $meta = [];

    /**
     * Parse Apache Tika response filling all properties.
     *
     * @param string $meta
     * @param string $file
     *
     * @throws \Exception
     */
    public function __construct($meta, $file)
    {
        $this->meta = $meta;

        // process each meta
        foreach ($this->meta as $key => $value) {
            $this->setAttribute($key, $value);
        }

        // file name without extension if title is not detected
        if (empty($this->title) && !is_null($file)) {
            $this->title = preg_replace('/\..+$/', '', basename($file));
        }

        // use creation date as last modified if not detected
        if (empty($this->updated)) {
            $this->updated = $this->created;
        }
    }

    /**
     * Return an instance of Metadata based on content type.
     *
     * @param string $response
     * @param string $file
     *
     * @return \Vaites\ApacheTika\Metadata\Metadata
     *
     * @throws \Exception
     */
    public static function make($response, $file)
    {
        $meta = json_decode($response);

        // exceptions if metadata is not valid
        if (json_last_error()) {
            throw new Exception(json_last_error_msg());
        } elseif (empty($meta)) {
            throw new Exception('Empty response');
        }

        // get content type
        if (is_array($meta->{'Content-Type'})) {
            $mime = current($meta->{'Content-Type'});
        } else {
            $mime = $meta->{'Content-Type'};
        }

        // instance based on content type
        switch (current(explode('/', $mime))) {
            case 'image':
                $instance = new ImageMetadata($meta, $file);
                break;

            default:
                $instance = new DocumentMetadata($meta, $file);
        }

        return $instance;
    }

    /**
     * Sets an attribute.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return bool
     */
    abstract protected function setAttribute($key, $value);
}
