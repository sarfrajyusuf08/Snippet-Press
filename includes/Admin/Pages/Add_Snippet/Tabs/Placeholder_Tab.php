<?php

namespace SnippetPress\Admin\Pages\Add_Snippet\Tabs;

/**
 * Simple placeholder tab for upcoming functionality.
 */
class Placeholder_Tab {
    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $message;

    public function __construct( string $title, string $message ) {
        $this->title   = $title;
        $this->message = $message;
    }

    /**
     * Render the placeholder content panel.
     */
    public function render(): void {
        echo '<div class="sp-empty-panel">';
        echo '<h2>' . esc_html( $this->title ) . '</h2>';
        echo '<p>' . esc_html( $this->message ) . '</p>';
        echo '</div>';
    }
}
