<?php

namespace Store\Toys;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Message;
use Phalcon\Validation;
use Phalcon\Mvc\Model\Validator\Uniqueness;
use Phalcon\Validation\Validator\Url as UrlValidator;

class products extends Model
{
    public function validation()
    {
        $validator = new Validation();

        // Robot name must be unique
        

        $validator->add(
            'image', 
            new UrlValidator(
                [
                    'message' => ':field must be a url'
                ]
            )
        );

        // Year cannot be less than zero
        if ($this->price < 0) {
            $this->appendMessage(
                new Message('The year cannot be less than zero')
            );
        }

        // Check if any messages have been produced
        if ($this->validationHasFailed() === true) {
            return false;
        }
    }
}