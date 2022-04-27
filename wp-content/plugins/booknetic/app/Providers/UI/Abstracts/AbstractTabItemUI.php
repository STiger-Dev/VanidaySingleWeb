<?php

namespace BookneticApp\Providers\UI\Abstracts;

abstract class AbstractTabItemUI
{
    private $slug;
    private $title;
    private $priority;
    private $views = [];

    /* setters */

    /**
     * @param string $slug
     */
    public function __construct ( $slug )
    {
        $this->slug = $slug;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle ( $title )
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @param int $priority
     */
    public function setPriority ( $priority )
    {
        $this->priority = ( int ) $priority;

        if ( $priority > static::$lastItemPriority )
        {
            static::$lastItemPriority = $priority;
        }
    }

    /**
     * @param string $viewPath
     * @param array $data
     * @return $this
     */
    public function addView ( $viewPath, $data = [], $priority = 999 )
    {
        $this->views[] = [
            'path'      => $viewPath,
            'data'      => $data,
            'priority'  => $priority
        ];

        return $this;
    }

    /* getters */

    /**
     * @return string
     */
    public function getSlug ()
    {
        return $this->slug;
    }

    /**
     * @return string
     */
    public function getTitle ()
    {
        return $this->title;
    }

    /**
     * @return int
     */
    public function getPriority ()
    {
        return ! empty( $this->priority ) ? $this->priority : ++static::$lastItemPriority;
    }

    /**
     * @return array
     */
    public function getViews ()
    {
        return $this->views;
    }

    /**
     * @param array $sharedParameters
     * @return string
     */
    public function getContent ( $sharedParameters = [] )
    {
        ob_start();

        $views = $this->getViews();

        if ( ! empty( $views ) )
        {
            usort( $views, function ( $item1, $item2 ) {
                return ( $item1[ 'priority' ] == $item2[ 'priority' ] ? 0 : ( $item1[ 'priority' ] > $item2[ 'priority' ] ? 1 : -1 ) );
            } );

            foreach ( $views as $view )
            {
                $viewPath = $view[ 'path' ];

                if ( file_exists( $viewPath ) )
                {
                    if ( ! empty( $view[ 'data' ] ) && is_callable( $view[ 'data' ] ) )
                    {
                        $parameters = call_user_func_array( $view[ 'data' ], [ $sharedParameters ] );
                    }

                    $parameters = isset( $parameters ) ? $parameters : ( ! empty( $view[ 'data' ] ) ? $view[ 'data' ] : $sharedParameters );

                    require $viewPath;
                }
            }
        }

        return ( string ) ob_get_clean();
    }
}