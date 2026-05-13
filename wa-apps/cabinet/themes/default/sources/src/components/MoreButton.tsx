import React from "react";
import { useSvg } from "@scompiler/0003-product/.scompiler/hooks";

type Props = {
    id: string;
};

export default function({id}: Props) {
    const svg = useSvg();

    return (
        <div className="dropdown">
            <button
                className="btn btn-sa-muted btn-sm"
                type="button"
                id={id}
                data-bs-toggle="dropdown"
                aria-expanded="false"
                aria-label="More"
            >
                {svg('stroyka/dots-3x13')}
            </button>
            <ul className="dropdown-menu dropdown-menu-end" aria-labelledby={id}>
                <li><a className="dropdown-item" href="#">Edit</a></li>
                <li><a className="dropdown-item" href="#">Duplicate</a></li>
                <li><a className="dropdown-item" href="#">Add tag</a></li>
                <li><a className="dropdown-item" href="#">Remove tag</a></li>
                <li><hr className="dropdown-divider" /></li>
                <li><a className="dropdown-item text-danger" href="#">Delete</a></li>
            </ul>
        </div>
    );
}
