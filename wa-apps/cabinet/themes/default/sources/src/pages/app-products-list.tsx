import React from 'react';
import Layout from '../components/Layout';
import Image from '../components/Image';
import App from '../components/App';
import products from '../data/products.json';
import Price from "../components/Price";
import MoreButton from "../components/MoreButton";
import { useSvg } from "@scompiler/0003-product/.scompiler/hooks";
import PageHeader from "../components/PageHeader";
import { url } from "../components/utils";

export default function() {
    const svg = useSvg();
    const stockStyle = (status) => (
        {
            'in-stock': 'badge-sa-success',
            'out-of-stock': 'badge-sa-danger',
            'on-backorder': 'badge-sa-warning',
            'preorder': 'badge-sa-primary',
        }[status]
    );

    const filters = [
        {
            key: 'price',
            title: 'Price',
            type: 'range',
            min: 0,
            max: 2000,
            from: 0,
            to: 2000,
        },
        {
            key: 'categories',
            title: 'Categories',
            type: 'check',
            items: [
                {label: 'Power tools', checked: true},
                {label: 'Hand tools', checked: false},
                {label: 'Machine tools', checked: true},
                {label: 'Power machinery', checked: false},
                {label: 'Measurement', checked: false},
            ],
        },
        {
            key: 'product_type',
            title: 'Product type',
            type: 'radio',
            items: [
                {label: 'Simple', checked: true},
                {label: 'Variable', checked: false},
                {label: 'Digital', checked: false},
            ],
        },
        {
            key: 'brands',
            title: 'Brands',
            type: 'check',
            items: [
                {label: 'Brandix', checked: false},
                {label: 'FastWheels', checked: true},
                {label: 'FuelCorp', checked: true},
                {label: 'RedGate', checked: false},
                {label: 'Specter', checked: false},
                {label: 'TurboElectric', checked: false},
            ],
        },
    ];

    const table = (
        <table className="sa-datatables-init" data-order={'[[ 1, "asc" ]]'} data-sa-search-input="#table-search">
            <thead>
                <tr>
                    <th className="w-min" data-orderable="false">
                        <input type="checkbox" className="form-check-input m-0 fs-exact-16 d-block" aria-label="..." />
                    </th>
                    <th className="min-w-20x">Product</th>
                    <th>Category</th>
                    <th>Stock</th>
                    <th>Price</th>
                    <th className="w-min" data-orderable="false" />
                </tr>
            </thead>
            <tbody>
                {products.map((product, productIdx) => (
                    <tr key={productIdx}>
                        <td>
                            <input type="checkbox" className="form-check-input m-0 fs-exact-16 d-block" aria-label="..." />
                        </td>
                        <td>
                            <div className="d-flex align-items-center">
                                <a href={url('product')} className="me-4">
                                    <div className="sa-symbol sa-symbol--shape--rounded sa-symbol--size--lg">
                                        <Image src={product.images[0]} size={40} />
                                    </div>
                                </a>
                                <div>
                                    <a href={url('product')} className="text-reset">{product.name}</a>
                                    <div className="sa-meta mt-0">
                                        <ul className="sa-meta__list">
                                            <li className="sa-meta__item">
                                                ID: <span title="Click to copy product ID" className="st-copy">{product.id}</span>
                                            </li>
                                            <li className="sa-meta__item">
                                                SKU: <span title="Click to copy product SKU" className="st-copy">{product.sku}</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <a href={url('category')} className="text-reset">{product.category}</a>
                        </td>
                        <td>
                            {product.stock && (
                                <div className={`badge ${stockStyle(product.stock.status)}`}>
                                    {product.stock.label}
                                </div>
                            )}
                            {!product.stock && (
                                <div className="sa-dash" role="presentation" />
                            )}
                        </td>
                        <td>
                            <Price value={product.price} />
                        </td>
                        <td>
                            <MoreButton id={`product-context-menu-${productIdx}`} />
                        </td>
                    </tr>
                ))}
            </tbody>
        </table>
    );

    const sidebar = (
        <>
            <div className="sa-layout__sidebar-header">
                <div className="sa-layout__sidebar-title">Filters</div>
                <button
                    type="button"
                    className="sa-close sa-layout__sidebar-close"
                    aria-label="Close"
                    data-sa-layout-sidebar-close=""
                />
            </div>
            <div className="sa-layout__sidebar-body sa-filters">
                <ul className="sa-filters__list">
                    {filters.map((filter, filterIdx) => (
                        <li key={filterIdx} className="sa-filters__item">
                            <div className="sa-filters__item-title">{filter.title}</div>
                            <div className="sa-filters__item-body">
                                {['check', 'radio'].includes(filter.type) && (
                                    <ul className="list-unstyled m-0 mt-n2">
                                        {filter.items?.map((item, itemIdx) => (
                                            <li key={itemIdx}>
                                                <label className="d-flex align-items-center pt-2">
                                                    {filter.type === 'check' && (
                                                        <input type="checkbox" className="form-check-input m-0 me-3 fs-exact-16" defaultChecked={item.checked} />
                                                    )}
                                                    {filter.type === 'radio' && (
                                                        <input type="radio" className="form-check-input m-0 me-3 fs-exact-16" defaultChecked={item.checked} name={`filter-${filter.key}`} />
                                                    )}
                                                    {item.label}
                                                </label>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                                {filter.type === 'range' && (
                                    <div
                                        className="sa-filter-range"
                                        data-min={filter.min}
                                        data-max={filter.max}
                                        data-from={filter.from}
                                        data-to={filter.to}
                                    >
                                        <div className="sa-filter-range__slider" />

                                        <input type="hidden" value={filter.from} className="sa-filter-range__input-from" />
                                        <input type="hidden" value={filter.to} className="sa-filter-range__input-to" />
                                    </div>
                                )}
                            </div>
                        </li>
                    ))}
                </ul>
            </div>
        </>
    );

    const content = (
        <div className="card">
            <div className="p-4">
                <div className="row g-4">
                    <div className="col-auto sa-layout__filters-button">
                        <button className="btn btn-sa-muted btn-sa-icon fs-exact-16" data-sa-layout-sidebar-open="">
                            {svg('stroyka/filters-16')}
                        </button>
                    </div>
                    <div className="col">
                        <input
                            type="text"
                            placeholder="Start typing to search for products"
                            className="form-control form-control--search mx-auto"
                            id="table-search"
                        />
                    </div>
                </div>
            </div>

            <div className="sa-divider" />

            {table}
        </div>
    );

    return (
        <Layout>
            <App>
                <div className="mx-xxl-3 px-4 px-sm-5">
                    <PageHeader
                        title="Products"
                        actions={[
                            <a key="import" href="#" className="btn btn-secondary me-3">
                                Import
                            </a>,
                            <a key="new_product" href={url('product')} className="btn btn-primary">
                                New product
                            </a>,
                        ]}
                        breadcrumb={[
                            {title: 'Dashboard', url: url('dashboard')},
                            {title: 'Products', url: url('products-list')},
                        ]}
                    />
                </div>
                <div className="mx-xxl-3 px-4 px-sm-5 pb-6">
                    <div className="sa-layout">
                        <div className="sa-layout__backdrop" data-sa-layout-sidebar-close="" />
                        <div className="sa-layout__sidebar">
                            {sidebar}
                        </div>
                        <div className="sa-layout__content">
                            {content}
                        </div>
                    </div>
                </div>
            </App>
        </Layout>
    );
}
