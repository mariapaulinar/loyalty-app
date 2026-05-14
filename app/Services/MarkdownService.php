<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\DefaultAttributes\DefaultAttributesExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Block\Paragraph;
use Symfony\Component\Yaml\Yaml;

class MarkdownService
{
    /**
     * Convert a markdown file to HTML considering app locale.
     *
     * @param  string  $filename  The name of the markdown file without the extension.
     * @return string Translated markdown file content or the filename if the file does not exist.
     */
    public function trans(string $filename): string
    {
        $locale = app()->getLocale();
        $fallbackLocale = config('app.fallback_locale');
        $filePaths = [
            lang_path("{$locale}/md/{$filename}.md"),
            lang_path("{$fallbackLocale}/md/{$filename}.md"),
        ];

        foreach ($filePaths as $path) {
            if (File::exists($path)) {
                $content = File::get($path);
                $parsed = $this->parseFrontmatter($content);

                return $this->parseMarkdown($parsed['content']);
            }
        }

        return $filename;
    }

    /**
     * Get markdown content with frontmatter metadata.
     *
     * @param  string  $filename  The name of the markdown file without the extension.
     * @return array Array with 'meta' (frontmatter) and 'content' (HTML) keys.
     */
    public function transWithMeta(string $filename): array
    {
        $locale = app()->getLocale();
        $fallbackLocale = config('app.fallback_locale');
        $filePaths = [
            lang_path("{$locale}/md/{$filename}.md"),
            lang_path("{$fallbackLocale}/md/{$filename}.md"),
        ];

        foreach ($filePaths as $path) {
            if (File::exists($path)) {
                $content = File::get($path);
                $parsed = $this->parseFrontmatter($content);

                return [
                    'meta' => $parsed['meta'],
                    'content' => $this->parseMarkdown($parsed['content']),
                ];
            }
        }

        return [
            'meta' => ['title' => $filename, 'description' => '', 'icon' => 'file-text'],
            'content' => $filename,
        ];
    }

    /**
     * Parse YAML frontmatter from markdown content.
     *
     * @param  string  $content  The raw markdown content.
     * @return array Array with 'meta' and 'content' keys.
     */
    private function parseFrontmatter(string $content): array
    {
        $meta = [];
        $markdown = $content;

        // Check for YAML frontmatter (starts with ---)
        if (preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $content, $matches)) {
            try {
                $meta = Yaml::parse($matches[1]);
                $markdown = $matches[2];
            } catch (\Exception $e) {
                // If YAML parsing fails, treat the whole thing as content
                $meta = [];
                $markdown = $content;
            }
        }

        return [
            'meta' => $meta ?? [],
            'content' => $markdown,
        ];
    }

    /**
     * Parse markdown content to HTML with custom styles.
     *
     * @param  string  $content  The markdown content to be converted.
     * @return string Converted HTML content.
     */
    public function parseMarkdown(string $content): string
    {
        $config = [
            'default_attributes' => [
                Heading::class => ['class' => fn (Heading $node) => $this->getHeadingClasses($node)],
                Paragraph::class => ['class' => 'mb-5 leading-[1.85]'],
                ListBlock::class => ['class' => 'mb-6 pl-5 space-y-2 list-disc'],
                ListItem::class => ['class' => 'leading-[1.8] text-secondary-600 dark:text-secondary-400'],
                Link::class => ['class' => 'text-link', 'target' => '_blank'],
            ],
        ];

        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new DefaultAttributesExtension);

        $converter = new MarkdownConverter($environment);

        return $converter->convert($content);
    }

    /**
     * Return classes based on heading level.
     *
     * Uses design system tokens (secondary-*) for neutral text.
     * These classes provide structural hierarchy; Tailwind prose
     * in Blade templates handles the remaining typographic polish.
     *
     * @param  Heading  $node  The heading node.
     * @return string|null The CSS classes to apply.
     */
    private function getHeadingClasses(Heading $node): ?string
    {
        return match ($node->getLevel()) {
            1 => 'mt-10 mb-5 text-3xl tracking-tight font-bold text-secondary-900 dark:text-white',
            2 => 'mt-10 mb-4 text-2xl tracking-tight font-bold text-secondary-900 dark:text-white',
            3 => 'mt-8 mb-3 text-xl font-semibold text-secondary-900 dark:text-white',
            4 => 'mt-6 mb-2 text-lg font-semibold text-secondary-800 dark:text-secondary-100',
            default => null,
        };
    }
}
