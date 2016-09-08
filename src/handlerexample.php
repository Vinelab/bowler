<?php

Registrator::queue('books', 'BooksHandler', ['type' => 'fanout']);

Registrator::queue('books', 'App\Messaging\BooksHandler');