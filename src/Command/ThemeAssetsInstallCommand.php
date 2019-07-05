<?php
namespace EMMWeb\Command;

use EMMWeb\Util\Functions;
use EMMWeb\Util\Theme;
use Patchwork\JSqueeze;
use ScssPhp\ScssPhp\Compiler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;


class ThemeAssetsInstallCommand extends Command
{
    const METHOD_COPY = 'copy';
    const METHOD_ABSOLUTE_SYMLINK = 'absolute symlink';
    const METHOD_RELATIVE_SYMLINK = 'relative symlink';

    protected static $defaultName = 'themeAssets:install';

    private $filesystem;
    private $projectDir;

    public function __construct(Filesystem $filesystem, string $projectDir = null)
    {
        parent::__construct();

        if (null === $projectDir) {
            @trigger_error(sprintf('Not passing the project directory to the constructor of %s is deprecated since Symfony 4.3 and will not be supported in 5.0.', __CLASS__), E_USER_DEPRECATED);
        }

        $this->filesystem = $filesystem;
        $this->projectDir = $projectDir;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Installs themes assets under a public directory')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ContainerInterface $container */
        $container = $this->getApplication()->getKernel()->getContainer();

        $io = new SymfonyStyle($input, $output);
        $io->newLine();
	    $expectedMethod = self::METHOD_COPY;
	    $io->text('Installing assets as <info>hard copies</info>.');
	    $io->newLine();

	    $rows = [];
	    $exitCode = 0;
	    $validAssetDirs = [];
	    $themesPublicDir = Functions::getPublicDirectory($this->projectDir).Theme::THEMES_PUBLIC_DIR;

	    //process any non-child theme
	    /** @var Finder $finder */
	    $finder = Finder::create()->in($container->getParameter('themes_source_dir'))->depth(0)->directories()->notName('*'.Theme::CHILD_THEME_APPEND);
	    foreach ($finder as $theme) {
		    //copy public files/images as hard copies
		    $originDir = $theme->getPathname().Theme::THEME_RESOURCES_PUBLIC_DIR;
		    $hasChild = Theme::doesChildThemeExists($theme->getFilename(), $container->getParameter('themes_source_dir'));
		    $childOriginDir = Theme::getChildTheme($theme->getPathname()).Theme::THEME_RESOURCES_PUBLIC_DIR;
			if (!(is_dir($originDir) || ($hasChild && is_dir($childOriginDir)))) {
				continue;
			}

			//theme is valid for public dir
		    $targetDir = $themesPublicDir.'/'.$theme->getFilename().'/'.$container->getParameter('app_asset_version');
		    $validAssetDirs[] = $theme->getFilename();

		    try {
			    //remove targetDir old files
			    $this->filesystem->remove($targetDir);
			    if (is_dir($originDir)) {
				    $message = sprintf("Theme %s%s -> %s", $theme->getFilename(), Theme::THEME_RESOURCES_PUBLIC_DIR, $targetDir);
				    //copy current theme assets
				    $this->hardCopy($originDir, $targetDir);
				    $rows[] = [sprintf('<fg=green;options=bold>%s</>', '\\' === \DIRECTORY_SEPARATOR ? 'OK' : "\xE2\x9C\x94" /* HEAVY CHECK MARK (U+2714) */), $message, $expectedMethod];
			    }

			    if (is_dir($childOriginDir)) {
			    	//child will override parent files if they have same names
				    $message = sprintf("Child theme %s%s -> %s", $theme->getFilename(), Theme::THEME_RESOURCES_PUBLIC_DIR, $targetDir);
				    //copy current theme assets
				    $this->hardCopy($childOriginDir, $targetDir);
				    $rows[] = [sprintf('<fg=green;options=bold>%s</>', '\\' === \DIRECTORY_SEPARATOR ? 'OK' : "\xE2\x9C\x94" /* HEAVY CHECK MARK (U+2714) */), $message, $expectedMethod];
			    }

			    //build js, css from files already copied to targetDir
			    //merge all theme css,scss into one
			    $scssContent = $this->getFilesContent($targetDir, ['*.css', '*.scss']);
			    if (!empty($scssContent)) {
				    $scss = new Compiler();
				    $scss->setFormatter('ScssPhp\ScssPhp\Formatter\Crunched');
				    $cssContent = $scss->compile($scssContent);
				    $this->filesystem->dumpFile($targetDir.'/style.css',$cssContent);
			    }

			    //merge all theme js into one
			    $jsContent = $this->getFilesContent($targetDir, ['*.js']);
			    if (!empty($jsContent)) {
				    $jsqueeze = new JSqueeze();
				    $jsContent = $jsqueeze->squeeze($jsContent);
				    $this->filesystem->dumpFile($targetDir.'/script.js',$jsContent);
			    }
		    } catch (\Exception $e) {
			    $exitCode = 1;
			    $rows[] = [sprintf('<fg=red;options=bold>%s</>', '\\' === \DIRECTORY_SEPARATOR ? 'ERROR' : "\xE2\x9C\x98" /* HEAVY BALLOT X (U+2718) */), $theme->getFilename(), $e->getMessage()];
		    }
	    }

	    //remove not existent themes
	    $dirsToRemove = Finder::create()->depth(0)->directories()->exclude($validAssetDirs)->in($themesPublicDir);
	    $this->filesystem->remove($dirsToRemove);
	    //remove older unused versions
	    foreach ($validAssetDirs as $validAssetDir) {
		    $dirsToRemove = Finder::create()->depth(0)->directories()->exclude($container->getParameter('app_asset_version'))->in($themesPublicDir.'/'.$validAssetDir);
		    $this->filesystem->remove($dirsToRemove);
	    }

        if ($rows) {
            $io->table(['', 'Theme', 'Method / Error'], $rows);
        }

        if (0 !== $exitCode) {
            $io->error('Some errors occurred while installing assets.');
        } else {
        	$io->note('Some assets were installed via copy. If you make changes to these assets you have to run this command again.');
            $io->success($rows ? 'All assets were successfully installed.' : 'No assets were provided by any bundle.');
        }

        return $exitCode;
    }

	/**
	 * Copies origin to target.
	 * @param string $originDir
	 * @param string $targetDir
	 * @return string
	 */
    private function hardCopy(string $originDir, string $targetDir): string
    {
        $this->filesystem->mkdir($targetDir, 0777);
        // We use a custom iterator to ignore VCS files
        $this->filesystem->mirror($originDir, $targetDir, Finder::create()->ignoreDotFiles(false)->in($originDir), ['override' => true]);

        return self::METHOD_COPY;
    }

    private function getFilesContent($dir, $patterns)
    {
	    $content = '';
	    $finder = Finder::create()->in($dir)->files()->name($patterns);
	    foreach ($finder as $file) {
		    $content .= file_get_contents($file);
	    }

	    return $content;
    }
}
