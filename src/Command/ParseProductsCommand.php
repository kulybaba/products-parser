<?php

namespace App\Command;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:parse:products',
    description: 'Parsing products by category URL',
    aliases: ['app:parse-products'],
)]
class ParseProductsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductRepository $productRepository,
        #[Autowire(env: 'CATEGORY_URL')]
        private string $categoryUrl,
        #[Autowire(env: 'PAGE_PATH')]
        private string $pagePath,
        #[Autowire(env: 'COUNT_PAGES')]
        private int $countPages,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Parsing products from ' . $this->categoryUrl);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $products = $this->parseProducts($this->categoryUrl, $this->pagePath, $this->countPages);
        if (empty($products)) {
            $io->info('Not products');

            return Command::INVALID;
        }

        $existProducts = $this->productRepository->findBySlugs(array_column($products, 'slug'));
        foreach ($products as $item) {
            $product = $existProducts[$item['slug']] ?? new Product();
            $product
                ->setName($item['name'])
                ->setSlug($item['slug'])
                ->setPrice($item['price'])
                ->setImageUrl($item['imageUrl'])
                ->setProductUrl($item['productUrl']);

            $this->entityManager->persist($product);
        }

        try {
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            $io->error('Error saving to DB: ' . $e->getMessage());

            return Command::FAILURE;
        }

        $io->success('SUCCESS');

        return Command::SUCCESS;
    }

    private function parseProducts(string $categoryUrl, string $pagePath, int $countPages = 3): array
    {
        $products = [];
        foreach (range(1, $countPages) as $page) {
            $products = array_merge($products, $this->parsePage($categoryUrl, $pagePath, $page));
        }

        return $products;
    }

    private function parsePage(string $categoryUrl, string $pagePath, int $page = 1): array
    {
        libxml_use_internal_errors(true);

        $domDocument = new \DOMDocument();
        $domDocument->loadHTMLFile(sprintf('%s/%s%s/', trim($categoryUrl, '/'), $pagePath, $page));
        $xpath = new \DOMXPath($domDocument);

        $products = [];
        $productNodes = $xpath->query('//div[@id="view-grid"]//div[contains(@class, "product-wrapper")]');
        foreach ($productNodes as $productNode) {
            $name = $xpath
                ->query('.//h3[contains(@class, "br-pp-desc")]/a/text()', $productNode)
                ->item(0)
                ->nodeValue;
            $slug = $productNode
                ->getAttribute('data-slug');
            $price = $xpath
                ->query('.//div[contains(@class, "br-pp-price")]/span/text()', $productNode)
                ->item(0)
                ->nodeValue;

            $linkNode = $xpath
                ->query('.//div[contains(@class, "br-pp-img")]/a', $productNode)
                ->item(0);
            $productUrl = $linkNode
                ->getAttribute('href');
            $imageUrl = $xpath
                ->query('.//img', $linkNode)
                ->item(0)
                ->getAttribute('data-observe-src');

            $products[] = [
                'name' => preg_replace('/\s+/', ' ', mb_convert_encoding(trim($name), 'ISO-8859-1')),
                'slug' => $slug,
                'price' => $price,
                'imageUrl' => $imageUrl,
                'productUrl' => $productUrl,
            ];
        }

        return $products;
    }
}
