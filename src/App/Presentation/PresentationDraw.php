<?php

namespace App\Presentation;

use Exception;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\Shape\Chart;
use PhpOffice\PhpPresentation\Shape\Chart\Gridlines;
use PhpOffice\PhpPresentation\Shape\Chart\Legend;
use PhpOffice\PhpPresentation\Shape\Chart\Series;
use PhpOffice\PhpPresentation\Shape\Chart\Type\AbstractType;
use PhpOffice\PhpPresentation\Shape\Chart\Type\Area;
use PhpOffice\PhpPresentation\Shape\Chart\Type\Bar;
use PhpOffice\PhpPresentation\Shape\Chart\Type\Line;
use PhpOffice\PhpPresentation\Shape\Chart\Type\Pie;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Border;
use PhpOffice\PhpPresentation\Style\Color;
use PhpOffice\PhpPresentation\Style\Fill;
use PhpOffice\PhpPresentation\Slide as DrawSlide;
use PhpOffice\PhpPresentation\Style\Outline;

/**
 * @author Andrey Mostovoy
 */
class PresentationDraw {
    /**
     * @var Presentation
     */
    private $Presentation;

    /**
     * @var PhpPresentation
     */
    private $Draw;

    /**
     * @var DrawSlide
     */
    private $DrawSlide;

    /**
     * @var int Количество диаграм на слайд.
     */
    private $diagramPerSlide = 1;

    /**
     * @var int Текущий номер диаграммы на слайде.
     */
    private $currentDiagram = 1;

    /**
     * @param Presentation $Presentation
     */
    public function __construct(Presentation $Presentation) {
        $this->Presentation = $Presentation;
        $this->Draw = new PhpPresentation();

        // Set properties
        $this->Draw->getDocumentProperties()
                   ->setCompany('tf team')
                   ->setCreator('PHPOffice')
                   ->setLastModifiedBy('YouScan Presentation')
                   ->setTitle('Title')
                   ->setSubject('Subject')
                   ->setDescription('Description')
                   ->setKeywords('office 2007 openxml libreoffice odt php')
                   ->setCategory('Category');
    }

    /**
     * Создает презентацию. Наполняет объект данными для дальнейшего вывода.
     * @return PresentationDraw
     * @throws Exception
     */
    public function createPpt(): self {
        // Remove first slide
        $this->Draw->removeSlideByIndex(0);

        foreach ($this->Presentation->Slides as $Slide) {
            $this->drawSlide($Slide);
        }

        return $this;
    }

    /**
     * Формирует слайд.
     * @param Slide $Slide
     * @throws Exception
     */
    private function drawSlide(Slide $Slide) {
        // Слайд
        $this->DrawSlide = $this->Draw->createSlide();

        if ($Slide->Setting->title) {
            // Заголовок слайда
            $TextShape = $this->DrawSlide->createRichTextShape();
            $TextShape
                ->setWidth(800)->setHeight(100)
                ->setOffsetX(80)->setOffsetY(80);
            $TextShape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $TextRun = $TextShape->createTextRun($Slide->Setting->title);
            $TextRun->getFont()->setBold(true)
                    ->setSize(30)
                    ->setColor(new Color(Color::COLOR_BLACK));
        }
        // Add logo
//        $Shape = $DrawSlide->createDrawingShape();
//        $Shape->setName('PHPPresentation logo')
//              ->setDescription('PHPPresentation logo')
//              ->setPath('./resources/phppowerpoint_logo.gif')
//              ->setHeight(36)
//              ->setOffsetX(10)
//              ->setOffsetY(10);
//        $Shape->getShadow()->setVisible(true)
//              ->setDirection(45)
//              ->setDistance(10);

        $this->diagramPerSlide = count($Slide->Diagrams);
        $this->currentDiagram = 0;
        foreach ($Slide->Diagrams as $Diagram) {
            $this->currentDiagram += 1;
            $this->drawDiagram($Diagram);
        }
    }

    /**
     * Формирует график диаграммы на слайде
     * @param Diagram $Diagram
     * @throws Exception
     */
    private function drawDiagram(Diagram $Diagram) {
        switch ($Diagram->type) {
            case 'line': // Линейный график
                $this->drawLineChart($Diagram);
                break;
            case 'area': // Залитый линейный график
                $this->drawAreaChart($Diagram);
                break;
            case 'pie': // Круговая гистограмма
                $this->drawPieChart($Diagram);
                break;
            case 'verticalBar': // столбчатые диаграммы
                $this->drawVerticalBarChart($Diagram);
                break;
            case 'horizontalBar': // линейные диаграммы
                $this->drawHorizontalBarChart($Diagram);
                break;
        }
    }

    /**
     * Формирует залитый линейный график.
     * @param Diagram $Diagram
     * @throws Exception
     */
    private function drawAreaChart(Diagram $Diagram) {
        $AreaChart = new Area();

        $this->addSeriesToChart($AreaChart, $Diagram);

        // Create a shape (chart)
        $ChartShape = $this->getChartShape($Diagram);
        $ChartShape->getLegend()->setVisible(false);
        $ChartShape->getPlotArea()->setType($AreaChart);
    }

    /**
     * Формирует линейный график.
     * @param Diagram $Diagram
     * @throws Exception
     */
    private function drawLineChart(Diagram $Diagram) {
        $LineChart = new Line();

        $this->addSeriesToChart($LineChart, $Diagram);

        // Create a shape (chart)
        $ChartShape = $this->getChartShape($Diagram);
        $ChartShape->getPlotArea()->setType($LineChart);

        if (count($LineChart->getSeries()) > 10) {
            $ChartShape->getLegend()->setWidth(520);
            $ChartShape->getLegend()->setOffsetX(0.1);
        }
    }

    /**
     * Формирует круговую диаграмму.
     * @param Diagram $Diagram
     * @throws Exception
     */
    private function drawPieChart(Diagram $Diagram) {
        $PieChart = new Pie();
        $PieChart->setExplosion(20);

        $this->addSeriesToChart($PieChart, $Diagram);

        foreach ($PieChart->getSeries() as $Series) {
            $Series->setShowValue(true);
            $Series->setLabelPosition(Series::LABEL_OUTSIDEEND);
            $Series->setShowPercentage(true);
            $Series->setShowValue(false);
            $Series->setShowSeriesName(false);
            $Series->setShowCategoryName(false);
            $Series->setDlblNumFormat('#%');
        }

        // Create a shape (chart)
        $ChartShape = $this->getChartShape($Diagram);
        $ChartShape->getPlotArea()->setType($PieChart);

        if (count($PieChart->getSeries()) > 10) {
            $ChartShape->getLegend()->setWidth(520);
            $ChartShape->getLegend()->setOffsetX(0.1);
        }
    }

    /**
     * Формирует столбчатую вертикальную диаграмму.
     * @param Diagram $Diagram
     * @throws Exception
     */
    private function drawVerticalBarChart(Diagram $Diagram) {
        $VerticalBarChart = new Bar();
        if (is_array(current($Diagram->data))) {
            $VerticalBarChart->setBarGrouping(Bar::GROUPING_STACKED);
        }

        $this->addSeriesToChart($VerticalBarChart, $Diagram);

        // Create a shape (chart)
        $ChartShape = $this->getChartShape($Diagram);
        $ChartShape->getPlotArea()->setType($VerticalBarChart);

        // потому что в процентах они
        if ($Diagram->section == 'sources.sentiment' ||
            $Diagram->section == 'tags.sentiment'
        ) {
            $ChartShape->getPlotArea()->getAxisY()->setMaxBounds(100);
        } else {
            $ChartShape->getLegend()->setVisible(false);
        }
    }

    /**
     * Формирует столбчатую горизонтальную диаграмму.
     * @param Diagram $Diagram
     * @throws Exception
     */
    private function drawHorizontalBarChart(Diagram $Diagram) {
        $HorizontalBarChart = new Bar();
        if (is_array(current($Diagram->data))) {
            $HorizontalBarChart->setBarGrouping(Bar::GROUPING_STACKED);
        }
        $HorizontalBarChart->setBarDirection(Bar::DIRECTION_HORIZONTAL);

        $this->addSeriesToChart($HorizontalBarChart, $Diagram);

        // Create a shape (chart)
        $ChartShape = $this->getChartShape($Diagram);
        $ChartShape->getPlotArea()->setType($HorizontalBarChart);
    }

    /**
     * Добавляет данные на график с базовыми настройками.
     * @param AbstractType $Chart
     * @param Diagram $Diagram
     * @throws Exception
     */
    private function addSeriesToChart(AbstractType $Chart, Diagram $Diagram) {
        $drawConfig = $Diagram->getDrawConfig();
        $translate = $Diagram->getTranslate();

        if (is_array(current($Diagram->data))) {
            // если тут - значит диаграмма имеем несколько наборов данных (несколько линий например)
            foreach ($Diagram->data as $group => $groupData) {
                // Набор данных
                $Series = new Series($translate[$group] ?? $group, $groupData);
                $Series->setShowSeriesName(false);
                $Series->setShowValue(false);
                $Series->getFill()->setFillType(Fill::FILL_SOLID);
                if (!isset($drawConfig[$group])) {
                    $colorARGB = $this->getARGB(mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), 1);
                } else {
                    $colorARGB = $drawConfig[$group];
                }
                $Series->getFill()->setStartColor(new Color($colorARGB));
                $Chart->addSeries($Series);
            }
        } else {
            // Набор данных
            $indexToKey = array_keys($Diagram->data);
            $data = [];
            foreach ($indexToKey as $index => $key) {
                if (!isset($translate[$key])) {
                    continue;
                }

                $data[$translate[$key]] = $Diagram->data[$key];
            }

            if ($data) {
                $Diagram->data = $data;
            }

            $Series = new Series($Diagram->name, $Diagram->data);
            $Series->setShowSeriesName(false);
            $Series->setShowValue(false);
            $Series->getFill()->setFillType(Fill::FILL_SOLID);
            $Series->getFill()->setStartColor(new Color($this->getARGB(33, 150, 243, 0.5)));
            $Chart->addSeries($Series);
        }
    }

    /**
     * Создает форму графика с базовыми параметрами.
     * @param Diagram $Diagram
     * @return Chart
     * @throws Exception
     */
    private function getChartShape(Diagram $Diagram): Chart {
        // Линии осей
        $OutlineAxis = new Outline();
        $OutlineAxis->setWidth(1);
        $OutlineAxis->getFill()->setFillType(Fill::FILL_SOLID);
        $OutlineAxis->getFill()->getStartColor()->setARGB(Color::COLOR_BLACK);

        // сетка
        $GridLine = new Gridlines();
        $GridLine->getOutline()->setWidth(1);
        $GridLine->getOutline()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('d3d3d3'); // серый

        // сам график/диаграмма/гистограмма
        $Chart = $this->DrawSlide->createChartShape();

        $Chart->getTitle()->setVisible((bool) $Diagram->name);
        $Chart->getTitle()->setText($Diagram->name);
        $Chart->getTitle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $Chart->getTitle()->getFont()->setItalic(true);

        $Chart->setResizeProportional(false);
        $Chart->setWidth(900 / $this->diagramPerSlide);
        $Chart->setHeight(450);
        $Chart->setOffsetX(30 + (900 / $this->diagramPerSlide) * ($this->currentDiagram - 1));
        $Chart->setOffsetY(200);
        $Chart->getShadow()->setVisible(false);
        $Chart->getShadow()->setDirection(45);
        $Chart->getShadow()->setDistance(10);
        $Chart->getBorder()->setLineStyle(Border::LINE_NONE);

        $Chart->getPlotArea()->getAxisX()->setTitle('');
        $Chart->getPlotArea()->getAxisY()->setTitle('');
        $Chart->getPlotArea()->getAxisX()->setOutline($OutlineAxis);
        $Chart->getPlotArea()->getAxisY()->setOutline($OutlineAxis);
        $Chart->getPlotArea()->getAxisY()->setMajorGridlines($GridLine);

//        $Shape->getView3D()->setRotationX(30);
//        $Shape->getView3D()->setPerspective(30);

        $Chart->getLegend()->setVisible(true);
        $Chart->getLegend()->getBorder()->setLineStyle(Border::LINE_NONE);
        $Chart->getLegend()->getFont()->setItalic(true);
        $Chart->getLegend()->getFont()->setSize(14);
        $Chart->getLegend()->setPosition(Legend::POSITION_BOTTOM);
        $Chart->getLegend()->setWidth(250);
        $Chart->getLegend()->setOffsetX(0.25);
        $Chart->getLegend()->setOffsetY(1);

        return $Chart;
    }

    /**
     * Возвращает объект для записи презентации.
     * @return \PhpOffice\PhpPresentation\Writer\WriterInterface
     */
    public function createWriter() {
        return IOFactory::createWriter($this->Draw, 'PowerPoint2007');
    }

    /**
     * Возвращает ARGB из RGBA.
     * @param int $red
     * @param int $green
     * @param int $blue
     * @param float $alpha
     * @return string
     */
    private function getARGB(int $red, int $green, int $blue, float $alpha = 1.0): string {
        return dechex((int) ($alpha * 255)) . dechex($red) . dechex($green) . dechex($blue);
    }
}
