<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Order;

/**
 * Thermal printer languages `get_label` can emit instead of a PDF.
 *
 * Omitting the format gives a PDF, which is what most integrations want. Ask for one of these only
 * when streaming straight to a label printer, and use the enum rather than a string: a value outside
 * it is not reported as an error, so a typo yields the wrong file type instead.
 *
 * Not every carrier can produce every language, and a PDF is what arrives where one cannot.
 */
enum LabelFileFormatEnum: string
{
    /**
     * Zebra Programming Language at the printer's default density.
     */
    case Zpl = 'ZPL';

    /**
     * Zebra Programming Language rendered for 300 dpi printers.
     */
    case Zpl300 = 'ZPL300';

    /**
     * Datamax printer language.
     */
    case Datamax = 'DATAMAX';

    /**
     * Eltron Programming Language.
     */
    case Epl = 'EPL';
}
