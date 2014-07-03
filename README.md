Global content blocks
=====================

Adds global content blocks to WordPress. Call them by using a template tag or a shortcode.

### Template tag

	<?php the_gc( 'testimonials' ); ?>

### Shortcode

	[gc name="testimonials"]

### Create post
You can also just create a post and a template tag and/or shortcode will be generated.

## Hooks

### tp_gc_args( $args )
Allows you to setup the global content post type differently. Usage:

	<?php add_filter( 'tp_gc_args', 'my_adjustment_function' ); ?>