# Mygento Mygento_Slider
## Функционал модуля
Данный модуль создавать и управлять сущностями слайдера с баннерами и слайдера с набором продуктов.

### GraphQl
- `GetProductSlider` возвращает данные продуктового слайдера
```graphql
query GetProductSlider($identity: String!) {
    productSlider(identity: $identity) {
        title
        identity
        options {
            autoplay
            arrows
        }
        items {
            image_formats {
                avif {
                    image {
                        size
                        link
                    }
                }
                webp {
                    image {
                        size
                        link
                    }
                }
                jpg {
                    image {
                        size
                        link
                    }
                }
            }
            product {
                name
                sku
                special_price
                price_range {
                    minimum_price {
                        regular_price {
                            value
                            currency
                        }
                        final_price {
                            value
                            currency
                        }
                    }
                }
            }
        }
    }
}
```
- `GetSliderById` возвращает данные слайдера и его баннеров
```graphql
query GetSliderById($identity: String!) {
    slider(identity: $identity) {
        title
        identity
        options {
            dots
            width
            arrows
            height
            preload
            autoplay
            infinite
            lazyLoad
            per_page
            breakpoint
            width_small
            height_small
            autoplay_interval
        }
        banners {
            name
            link
            image_formats {
                avif {
                    image {
                        size
                        link
                    }
                    small_image {
                        size
                        link
                    }
                }
                webp {
                    image {
                        size
                        link
                    }
                    small_image {
                        size
                        link
                    }
                }
                jpg {
                    image {
                        size
                        link
                    }
                    small_image {
                        size
                        link
                    }
                }
            }
        }
    }
}
```

## Observers
- `\Mygento\Slider\Observer\PreloadBanner` - предзагрузка баннеров.

# Global

# Adminhtml
- Content -> Banner Sliders
