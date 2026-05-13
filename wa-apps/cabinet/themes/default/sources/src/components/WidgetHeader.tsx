import { useSvg } from "@scompiler/0003-product/.scompiler/hooks";
import classnames from "classnames";
import React from "react";
import { useUniqueId } from "@scompiler/0003-product";

export default function({title, className}: {title: string; className?: string}) {
    const svg = useSvg();
    const id = useUniqueId('widget-context-menu');

    return (
        <div className={classnames('sa-widget-header', className)}>
            <h2 className="sa-widget-header__title">{title}</h2>
            <div className="sa-widget-header__actions">
                <div className="dropdown">
                    <button
                        type="button"
                        className="btn btn-sm btn-sa-muted d-block"
                        id={`widget-context-menu-${id}`}
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        aria-label="More"
                    >
                        {svg('stroyka/dots-3x13')}
                    </button>

                    <ul className="dropdown-menu dropdown-menu-end" aria-labelledby={`widget-context-menu-${id}`}>
                        <li><a className="dropdown-item" href="#">Settings</a></li>
                        <li><a className="dropdown-item" href="#">Move</a></li>
                        <li><hr className="dropdown-divider" /></li>
                        <li><a className="dropdown-item text-danger" href="#">Remove</a></li>
                    </ul>
                </div>
            </div>
        </div>
    );
}
