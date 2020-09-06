module.exports = {
  '**/*.php': ['php ./vendor/bin/php-cs-fixer fix --config .php_cs.dist --allow-risky=yes'],
};
