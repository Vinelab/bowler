<?php

Registrator::queue('books', 'BooksHandler', ['type' => 'fanout']);

Registrator::queue('books', 'App\Messaging\Handlers\BooksHandler');
