<?php

Registrator::queue('books', 'BooksHandler', ['type' => 'fanout']);

Registrator::queue('books', 'Books2Handler');