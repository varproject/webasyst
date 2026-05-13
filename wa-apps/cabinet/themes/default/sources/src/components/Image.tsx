import React from 'react';
import { useResize } from "@scompiler/0003-product/.scompiler/hooks";

type Size = {
    w: number;
    h: number;
};

type Props = {
    src: string;
    size?: number|[number, number];
    alt?: string;
    title?: string;
    className?: string;
};

export default function({src: srcProp, size: sizeProp, ...restProps}: Props) {
    const resize = useResize();
    let src;
    let imageProps: React.ImgHTMLAttributes<HTMLImageElement> = restProps;

    if (typeof sizeProp !== 'undefined') {
        const nSize: Size = typeof sizeProp === 'number'
            ? {w: sizeProp, h: sizeProp}
            : {w: sizeProp[0], h: sizeProp[1]};

        src = resize(srcProp, nSize.w, nSize.h);
        imageProps = {...imageProps, width: nSize.w, height: nSize.h};
    } else {
        src = resize(srcProp, -1, -1);
    }

    return (
        <img src={src} {...imageProps} alt="" />
    );
}
