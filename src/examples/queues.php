<?php

/**
 * Queue.
 *
 * @param string queue name
 * @param string handler class name
 */
Registrator::queue('authors', 'App\Messaging\Handlers\AuthorHandler');

/*
 * Queue
 *
 * @param string queue name
 * @param string handler class name
 * @param array  options
 */
Registrator::queue('books', 'App\Messaging\Handlers\BooksHandler', [
                                                        'exchangeName' => 'main_exchange',
                                                        'exchangeType' => 'direct',
                                                        'bindingKeys' => [
                                                            'warning',
                                                            'notification',
                                                        ],
                                                        'pasive' => false,
                                                        'durable' => true,
                                                        'autoDelete' => false,
                                                        'deliveryMode' => 2,
                                                        'deadLetterQueueName' => 'dlx_queue',
                                                        'deadLetterExchangeName' => 'dlx',
                                                        'deadLetterExchangeType' => 'direct',
                                                        'deadLetterRoutingKey' => 'warning',
                                                        'messageTTL' => null,
                                                    ]);

/*
 * Subscribe
 *
 * @param string queue name
 * @param string handler class name
 * @param array  binding keys
 */
Registrator::subscribe('reporting', 'App\Messaging\Handlers\BooksHandler', ['error', null]);
