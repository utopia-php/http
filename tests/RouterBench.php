<?php

namespace Utopia;

use PhpBench\Attributes\AfterMethods;
use PhpBench\Attributes\Assert;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\ParamProviders;

final class RouterBench
{
    public function tearDown(): void
    {
        Router::reset();
    }

    public function setUpRouter(): void
    {
        $routeBlog = new Route(App::REQUEST_METHOD_GET, '/blog');
        $routeBlogAuthors = new Route(App::REQUEST_METHOD_GET, '/blog/authors');
        $routeBlogPost = new Route(App::REQUEST_METHOD_GET, '/blog/:post');
        $routeBlogPostComments = new Route(App::REQUEST_METHOD_GET, '/blog/:post/comments');
        $routeBlogPostCommentsSingle = new Route(App::REQUEST_METHOD_GET, '/blog/:post/comments/:comment');
        $routeBlogLongUrl = new Route(App::REQUEST_METHOD_GET, '/blog/lorem/ipsum/dolor/sit/amet/consectetur/adipiscing/elit/Quisque/dolor/nisi/gravida/non/malesuada/eget/tincidunt/vitae/eros/Donec/hendrerit/mollis/purus/non/efficitur/augue/efficitur/sed/Praesent/a/tempus/felis/et/elementum/lorem/Vestibulum/ante/ipsum/primis/in/faucibus/orci/luctus/et/ultrices/posuere/cubilia/curae/Ut/luctus/ultrices/ligula/vulputate/malesuada/magna/pellentesque/eget/Mauris/at/sodales/orci/Mauris/efficitur/volutpat/est/in/faucibus/Donec/non/eleifend/nibh/Nunc/cursus/ornare/sollicitudin/Nullam/pellentesque/placerat/justo/ac/eleifend/tortor/imperdiet/quis/Nullam/tincidunt/non/justo/ut/pulvinar/Suspendisse/laoreet/tempus/nulla/eu/aliquet/Proin/metus/erat/facilisis/in/euismod/sit/amet/mollis/ac/nisi/Nulla/facilisi');

        Router::addRoute($routeBlog);
        Router::addRoute($routeBlogAuthors);
        Router::addRoute($routeBlogPost);
        Router::addRoute($routeBlogPostComments);
        Router::addRoute($routeBlogPostCommentsSingle);
        Router::addRoute($routeBlogLongUrl);
    }

    public function provideRoutesToMatch(): iterable
    {
        foreach ([
            'single' => '/blog',
            'nested' => '/blog/authors',
            'single param' => '/blog/lorem-ipsum',
            'single param with nested' => '/blog/lorem-ipsum/comments',
            'multiple params' => '/blog/lorem-ipsum/comments/1337',
            'long' => '/blog/lorem/ipsum/dolor/sit/amet/consectetur/adipiscing/elit/Quisque/dolor/nisi/gravida/non/malesuada/eget/tincidunt/vitae/eros/Donec/hendrerit/mollis/purus/non/efficitur/augue/efficitur/sed/Praesent/a/tempus/felis/et/elementum/lorem/Vestibulum/ante/ipsum/primis/in/faucibus/orci/luctus/et/ultrices/posuere/cubilia/curae/Ut/luctus/ultrices/ligula/vulputate/malesuada/magna/pellentesque/eget/Mauris/at/sodales/orci/Mauris/efficitur/volutpat/est/in/faucibus/Donec/non/eleifend/nibh/Nunc/cursus/ornare/sollicitudin/Nullam/pellentesque/placerat/justo/ac/eleifend/tortor/imperdiet/quis/Nullam/tincidunt/non/justo/ut/pulvinar/Suspendisse/laoreet/tempus/nulla/eu/aliquet/Proin/metus/erat/facilisis/in/euismod/sit/amet/mollis/ac/nisi/Nulla/facilisi'
        ] as $name => $route) {
            yield $name => ['route' => $route];
        }
    }

    #[BeforeMethods('setUpRouter')]
    #[AfterMethods('tearDown')]
    #[Iterations(50)]
    #[Assert('mode(variant.time.avg) < 0.1 ms')]
    #[ParamProviders('provideRoutesToMatch')]
    public function benchRouter(array $data): void
    {
        Router::match(App::REQUEST_METHOD_GET, $data['route']);
    }
}
