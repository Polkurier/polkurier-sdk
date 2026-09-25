<?php
/**
 * Copyright (c) 2026 Polkurier.pl
 * All Rights Reserved
 */
declare(strict_types=1);

namespace Polkurier\Webservice\Enum\Order;

/**
 * Thermal printer languages `get_label` can return instead of a PDF.
 *
 * Without a format you get a PDF, which is what most integrations need. Use one of these only when
 * sending straight to a label printer. Use a value from this enum.
 *
 * Not every carrier supports every language.
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
