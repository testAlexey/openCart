<?php
class ControllerProductSearch extends Controller {
	        
        public function  ajax() {
          $this->load->model('tool/image');

          if (isset($this->request->get['keyword'])) {
            $data = array();
            // Parse all keywords to lowercase
            $keywords = utf8_strtolower( $this->request->get['keyword'] );
            // Perform search only if we have some keywords
            if ( utf8_strlen($keywords) >= 3 ) {
              $parts = explode( ' ', $keywords );
              $add = '';
              // Generating search
              foreach( $parts as $part ) {
                $add .= ' AND (LOWER(pd.name) LIKE "%' . $this->db->escape($part) . '%"';
                $add .= ' OR LOWER(p.model) LIKE "%' . $this->db->escape($part) . '%")';
              }
              $add = substr( $add, 4 );
              $sql  = 'SELECT pd.product_id, pd.name, p.image, p.model FROM ' . DB_PREFIX . 'product_description AS pd ';
              $sql .= 'LEFT JOIN ' . DB_PREFIX . 'product AS p ON p.product_id = pd.product_id ';
              $sql .= 'LEFT JOIN ' . DB_PREFIX . 'product_to_store AS p2s ON p2s.product_id = pd.product_id ';
              $sql .= 'WHERE ' . $add . ' AND p.status = 1 ';
              $sql .= 'AND pd.language_id = ' . (int)$this->config->get('config_language_id');
              $sql .= ' AND p2s.store_id =  ' . (int)$this->config->get('config_store_id'); 
              $sql .= ' ORDER BY p.sort_order ASC, LOWER(pd.name) ASC, LOWER(p.model) ASC';
              $sql .= ' LIMIT 10';
              $res = $this->db->query( $sql );
              if ($res) {
                $data = ( isset($res->rows) ) ? $res->rows : $res->row;               
                // For the seo url stuff
                $basehref = 'product/product&keyword=' . $this->request->get['keyword'] . '&product_id=';
                foreach( $data as $key => $values ) {
                  if (isset($values['image']) && $values['image'] != "") {
                    $values['image'] = $this->model_tool_image->resize($values['image'], 60, 60);
                  }                 
                  $data[$key] = array(
                    'name' => htmlspecialchars_decode($values['name'] . ' (' . $values['model'] . ')', ENT_QUOTES),
                    'href' => $this->url->link($basehref . $values['product_id']),
                    'image' => $values['image']
                  );
                }
              }
            }
          }
          echo json_encode( $data );
        }
        
        public function index() {
		$this->load->language('product/search');

		$this->load->model('catalog/category');

		$this->load->model('catalog/product');

		$this->load->model('tool/image');

		if (isset($this->request->get['search'])) {
			$search = $this->request->get['search'];
		} else {
			$search = '';
		}

		if (isset($this->request->get['tag'])) {
			$tag = $this->request->get['tag'];
		} elseif (isset($this->request->get['search'])) {
			$tag = $this->request->get['search'];
		} else {
			$tag = '';
		}

		if (isset($this->request->get['description'])) {
			$description = $this->request->get['description'];
		} else {
			$description = '';
		}

		if (isset($this->request->get['category_id'])) {
			$category_id = $this->request->get['category_id'];
		} else {
			$category_id = 0;
		}

		if (isset($this->request->get['sub_category'])) {
			$sub_category = $this->request->get['sub_category'];
		} else {
			$sub_category = '';
		}


      $filter_products_data = $this->config->get('filter_products_data');
      $filter_products_status = $this->config->get('filter_products_status');
      
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];

      } elseif ($filter_products_status && $filter_products_data['default_sort']) {
        $sort = $filter_products_data['default_sort'];
      
		} else {
			$sort = 'p.sort_order';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];

      } elseif ($filter_products_status && $filter_products_data['default_order']) {
        $order = $filter_products_data['default_order'];
      
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		if (isset($this->request->get['limit'])) {
			$limit = (int)$this->request->get['limit'];
		} else {
			$limit = $this->config->get($this->config->get('config_theme') . '_product_limit');
		}

		if (isset($this->request->get['search'])) {
			$this->document->setTitle($this->language->get('heading_title') .  ' - ' . $this->request->get['search']);
		} elseif (isset($this->request->get['tag'])) {
			$this->document->setTitle($this->language->get('heading_title') .  ' - ' . $this->language->get('heading_tag') . $this->request->get['tag']);
		} else {
			$this->document->setTitle($this->language->get('heading_title'));
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$url = '';

		if (isset($this->request->get['search'])) {
			$url .= '&search=' . urlencode(html_entity_decode($this->request->get['search'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['tag'])) {
			$url .= '&tag=' . urlencode(html_entity_decode($this->request->get['tag'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['description'])) {
			$url .= '&description=' . $this->request->get['description'];
		}

		if (isset($this->request->get['category_id'])) {
			$url .= '&category_id=' . $this->request->get['category_id'];
		}

		if (isset($this->request->get['sub_category'])) {
			$url .= '&sub_category=' . $this->request->get['sub_category'];
		}


      $filter_products_data = $this->config->get('filter_products_data');
      $filter_products_status = $this->config->get('filter_products_status');
      
		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		if (isset($this->request->get['limit'])) {
			$url .= '&limit=' . $this->request->get['limit'];
		}

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('product/search', $url)
		);

		if (isset($this->request->get['search'])) {
			$data['heading_title'] = $this->language->get('heading_title') .  ' - ' . $this->request->get['search'];
		} else {
			$data['heading_title'] = $this->language->get('heading_title');
		}

		$data['text_empty'] = $this->language->get('text_empty');
		$data['text_search'] = $this->language->get('text_search');
		$data['text_keyword'] = $this->language->get('text_keyword');
		$data['text_category'] = $this->language->get('text_category');
		$data['text_sub_category'] = $this->language->get('text_sub_category');
		$data['text_quantity'] = $this->language->get('text_quantity');
		$data['text_manufacturer'] = $this->language->get('text_manufacturer');
		$data['text_model'] = $this->language->get('text_model');
		$data['text_price'] = $this->language->get('text_price');
		$data['text_tax'] = $this->language->get('text_tax');
		$data['text_points'] = $this->language->get('text_points');
		$data['text_compare'] = sprintf($this->language->get('text_compare'), (isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0));
		$data['text_sort'] = $this->language->get('text_sort');
		$data['text_limit'] = $this->language->get('text_limit');

		$data['entry_search'] = $this->language->get('entry_search');
		$data['entry_description'] = $this->language->get('entry_description');

		$data['button_search'] = $this->language->get('button_search');
		$data['button_cart'] = $this->language->get('button_cart');
		$data['button_wishlist'] = $this->language->get('button_wishlist');
		$data['button_compare'] = $this->language->get('button_compare');
		$data['button_list'] = $this->language->get('button_list');
		$data['button_grid'] = $this->language->get('button_grid');

		$data['compare'] = $this->url->link('product/compare');

		$this->load->model('catalog/category');

		// 3 Level Category Search
		$data['categories'] = array();

		$categories_1 = $this->model_catalog_category->getCategories(0);

		foreach ($categories_1 as $category_1) {
			$level_2_data = array();

			$categories_2 = $this->model_catalog_category->getCategories($category_1['category_id']);

			foreach ($categories_2 as $category_2) {
				$level_3_data = array();

				$categories_3 = $this->model_catalog_category->getCategories($category_2['category_id']);

				foreach ($categories_3 as $category_3) {
					$level_3_data[] = array(
						'category_id' => $category_3['category_id'],
						'name'        => $category_3['name'],
					);
				}

				$level_2_data[] = array(
					'category_id' => $category_2['category_id'],
					'name'        => $category_2['name'],
					'children'    => $level_3_data
				);
			}

			$data['categories'][] = array(
				'category_id' => $category_1['category_id'],
				'name'        => $category_1['name'],
				'children'    => $level_2_data
			);
		}

		$data['products'] = array();

		if (isset($this->request->get['search']) || isset($this->request->get['tag'])) {
			$filter_data = array(
				'filter_name'         => $search,
				'filter_tag'          => $tag,
				'filter_description'  => $description,
				'filter_category_id'  => $category_id,
				'filter_sub_category' => $sub_category,
				'sort'                => $sort,
				'order'               => $order,
				'start'               => ($page - 1) * $limit,
				'limit'               => $limit
			);

			$product_total = $this->model_catalog_product->getTotalProducts($filter_data);
       
        // popup_view start
        $data['popup_view_data'] = $this->config->get('popup_view_data');
        $data['popup_view_text'] = $this->language->load('extension/module/popup_view');
        // popup_view end
      

			$results = $this->model_catalog_product->getProducts($filter_data);

			foreach ($results as $result) {
				if ($result['image']) {
					$image = $this->model_tool_image->resize($result['image'], $this->config->get($this->config->get('config_theme') . '_image_product_width'), $this->config->get($this->config->get('config_theme') . '_image_product_height'));
				} else {
					$image = $this->model_tool_image->resize('placeholder.png', $this->config->get($this->config->get('config_theme') . '_image_product_width'), $this->config->get($this->config->get('config_theme') . '_image_product_height'));
				}

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$price = false;
				}

        
        if (preg_match("|new|", $result['tag'])) { 
          $novinka = true; 
        } else {
          $novinka = false;
        }

        if ((float)$result['special']) {
          $economy = round((($result['price'] - $result['special'])/($result['price'] + 0.01))*100, 0);
        } else {
          $economy = false;
        }
                
        $dop_img = false;
        $results_img = $this->model_catalog_product->getProductImages($result['product_id']);
             
        if ($results_img) {
          $dop_img = $this->model_tool_image->resize($results_img[0]['image'], $this->config->get($this->config->get('config_theme') . '_image_category_width'), $this->config->get($this->config->get('config_theme') . '_image_category_height'));
        }
      
				if ((float)$result['special']) {
					$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$special = false;
				}

				if ($this->config->get('config_tax')) {
					$tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
				} else {
					$tax = false;
				}

				if ($this->config->get('config_review_status')) {
					$rating = (int)$result['rating'];
				} else {
					$rating = false;
				}

        
        // oct_images_by_options start
        $oct_options = array();

        $images_by_options_data = $this->config->get('images_by_options_data');

        foreach ($this->model_catalog_product->getProductOptions($result['product_id']) as $option) {
          $product_option_value_data = array();

          if (isset($images_by_options_data['allowed_options']) && (in_array($option['option_id'], $images_by_options_data['allowed_options']))) {
            foreach ($option['product_option_value'] as $option_value) {
              if (!$option_value['subtract'] || ($option_value['quantity'] >= 0)) {
                
                if ((($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) && (float)$option_value['price']) {
                  $oct_option_price = $this->currency->format($this->tax->calculate($option_value['price'], $result['tax_class_id'], $this->config->get('config_tax') ? 'P' : false), $this->session->data['currency']);
                } else {
                  $oct_option_price = false;
                }

                $product_option_value_data[] = array(
                  'product_option_value_id' => $option_value['product_option_value_id'],
                  'option_value_id'         => $option_value['option_value_id'],
                  'name'                    => $option_value['name'],
                  'image'                   => ($option_value['image']) ? $this->model_tool_image->resize($option_value['image'], 50, 50) : '',
                  'price'                   => $oct_option_price,
                  'price_prefix'            => $option_value['price_prefix']
               );
              }
            }

            $oct_options[] = array(
              'product_option_id'    => $option['product_option_id'],
              'product_option_value' => $product_option_value_data,
              'option_id'            => $option['option_id'],
              'name'                 => $option['name'],
              'type'                 => $option['type'],
              'value'                => $option['value'],
              'required'             => $option['required']
            );
          }
        }
        // oct_images_by_options end
      
        
        $product_stickers_data = $this->config->get('product_stickers_data');
        $product_stickers = array();

        if (isset($product_stickers_data['status']) && $product_stickers_data['status']) {
          $this->load->model('catalog/product_stickers');

          if (isset($result['product_stickers']) && $result['product_stickers']) {
            $stickers = unserialize($result['product_stickers']);
          } else {
            $stickers = array();
          }

          if ($stickers) {
              foreach ($stickers as $product_sticker_id) {
                $sticker_info = $this->model_catalog_product_stickers->getProductSticker($product_sticker_id);
                
                if ($sticker_info) {
                  $product_stickers[] = array(
                    'text' => $sticker_info['text'],
                    'color' => $sticker_info['color'],
                    'background' => $sticker_info['background']
                  );
                }
              }
    
              $sticker_sort_order = array();
    
              foreach ($stickers as $key => $product_sticker_id) {
                $sticker_info = $this->model_catalog_product_stickers->getProductSticker($product_sticker_id);
                
                if ($sticker_info) {
                  $sticker_sort_order[$key] = $sticker_info['sort_order'];
                }
              }
              
              array_multisort($sticker_sort_order, SORT_ASC, $product_stickers);
          }
        }
      

				$oct_product_preorder_text = $this->config->get('oct_product_preorder_text');
				$oct_product_preorder_data = $this->config->get('oct_product_preorder_data');
				$oct_product_preorder_language = $this->load->language('extension/module/oct_product_preorder');
								
				if (isset($oct_product_preorder_data['status']) && $oct_product_preorder_data['status'] && isset($oct_product_preorder_data['stock_statuses']) && isset($result['oct_stock_status_id']) && in_array($result['oct_stock_status_id'], $oct_product_preorder_data['stock_statuses'])) {
					$product_preorder_text = $oct_product_preorder_text[$this->session->data['language']]['call_button'];
					$product_preorder_status = 1;
				} else {
					$product_preorder_text = $oct_product_preorder_language['text_out_of_stock'];
					$product_preorder_status = 2;
				}
			
				$data['products'][] = array(
        
        'product_stickers' => $product_stickers,
      
        
        // oct_images_by_options start
        'oct_options' => $oct_options,
        // oct_images_by_options end
      
					'product_id'  => $result['product_id'],
					'thumb'       => $image,
					'name'        => $result['name'],
					'description' => utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get($this->config->get('config_theme') . '_product_description_length')) . '..',
        
        'novinka'       => $novinka,
        'economy'       => $economy,
        'dop_img'       => $dop_img,
      

				'quantity'       => $result['quantity'], 
				'product_preorder_text' => $product_preorder_text,
				'product_preorder_status' => $product_preorder_status,
			
					'price'       => $price,
					'special'     => $special,
					'tax'         => $tax,
					'minimum'     => ($result['minimum'] > 0) ? $result['minimum'] : 1,
					'rating'      => $rating,
					'href'        => $this->url->link('product/product', 'product_id=' . $result['product_id'] . $url)
				);
			}

			$url = '';

			if (isset($this->request->get['search'])) {
				$url .= '&search=' . urlencode(html_entity_decode($this->request->get['search'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['tag'])) {
				$url .= '&tag=' . urlencode(html_entity_decode($this->request->get['tag'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['description'])) {
				$url .= '&description=' . $this->request->get['description'];
			}

			if (isset($this->request->get['category_id'])) {
				$url .= '&category_id=' . $this->request->get['category_id'];
			}

			if (isset($this->request->get['sub_category'])) {
				$url .= '&sub_category=' . $this->request->get['sub_category'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['sorts'] = array();

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_default'),
				
      'value' => ($filter_products_status && $filter_products_data['default_sort'] && $filter_products_data['default_order']) ? $filter_products_data['default_sort'].'-'.$filter_products_data['default_order'] : 'p.sort_order-ASC',
      
				
      'href'  => ($filter_products_status && $filter_products_data['default_sort'] && $filter_products_data['default_order']) ? $this->url->link('product/search', 'sort='.$filter_products_data['default_sort'].'&order='.$filter_products_data['default_order'].$url) : $this->url->link('product/search', 'sort=p.sort_order&order=ASC' . $url)
      
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_name_asc'),
				'value' => 'pd.name-ASC',
				'href'  => $this->url->link('product/search', 'sort=pd.name&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_name_desc'),
				'value' => 'pd.name-DESC',
				'href'  => $this->url->link('product/search', 'sort=pd.name&order=DESC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_price_asc'),
				'value' => 'p.price-ASC',
				'href'  => $this->url->link('product/search', 'sort=p.price&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_price_desc'),
				'value' => 'p.price-DESC',
				'href'  => $this->url->link('product/search', 'sort=p.price&order=DESC' . $url)
			);

			if ($this->config->get('config_review_status')) {
				$data['sorts'][] = array(
					'text'  => $this->language->get('text_rating_desc'),
					'value' => 'rating-DESC',
					'href'  => $this->url->link('product/search', 'sort=rating&order=DESC' . $url)
				);

				$data['sorts'][] = array(
					'text'  => $this->language->get('text_rating_asc'),
					'value' => 'rating-ASC',
					'href'  => $this->url->link('product/search', 'sort=rating&order=ASC' . $url)
				);
			}

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_model_asc'),
				'value' => 'p.model-ASC',
				'href'  => $this->url->link('product/search', 'sort=p.model&order=ASC' . $url)
			);

			$data['sorts'][] = array(
				'text'  => $this->language->get('text_model_desc'),
				'value' => 'p.model-DESC',
				'href'  => $this->url->link('product/search', 'sort=p.model&order=DESC' . $url)
			);

			$url = '';

			if (isset($this->request->get['search'])) {
				$url .= '&search=' . urlencode(html_entity_decode($this->request->get['search'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['tag'])) {
				$url .= '&tag=' . urlencode(html_entity_decode($this->request->get['tag'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['description'])) {
				$url .= '&description=' . $this->request->get['description'];
			}

			if (isset($this->request->get['category_id'])) {
				$url .= '&category_id=' . $this->request->get['category_id'];
			}

			if (isset($this->request->get['sub_category'])) {
				$url .= '&sub_category=' . $this->request->get['sub_category'];
			}


      $filter_products_data = $this->config->get('filter_products_data');
      $filter_products_status = $this->config->get('filter_products_status');
      
			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			$data['limits'] = array();

			$limits = array_unique(array($this->config->get($this->config->get('config_theme') . '_product_limit'), 25, 50, 75, 100));

			sort($limits);

			foreach($limits as $value) {
				$data['limits'][] = array(
					'text'  => $value,
					'value' => $value,
					'href'  => $this->url->link('product/search', $url . '&limit=' . $value)
				);
			}

			$url = '';

			if (isset($this->request->get['search'])) {
				$url .= '&search=' . urlencode(html_entity_decode($this->request->get['search'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['tag'])) {
				$url .= '&tag=' . urlencode(html_entity_decode($this->request->get['tag'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['description'])) {
				$url .= '&description=' . $this->request->get['description'];
			}

			if (isset($this->request->get['category_id'])) {
				$url .= '&category_id=' . $this->request->get['category_id'];
			}

			if (isset($this->request->get['sub_category'])) {
				$url .= '&sub_category=' . $this->request->get['sub_category'];
			}


      $filter_products_data = $this->config->get('filter_products_data');
      $filter_products_status = $this->config->get('filter_products_status');
      
			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$pagination = new Pagination();
			$pagination->total = $product_total;
			$pagination->page = $page;
			$pagination->limit = $limit;
			$pagination->url = $this->url->link('product/search', $url . '&page={page}');

			$data['pagination'] = $pagination->render();

			$data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($product_total - $limit)) ? $product_total : ((($page - 1) * $limit) + $limit), $product_total, ceil($product_total / $limit));

			// http://googlewebmastercentral.blogspot.com/2011/09/pagination-with-relnext-and-relprev.html
			if ($page == 1) {
			    $this->document->addLink($this->url->link('product/search', '', true), 'canonical');
			} elseif ($page == 2) {
			    $this->document->addLink($this->url->link('product/search', '', true), 'prev');
			} else {
			    $this->document->addLink($this->url->link('product/search', $url . '&page='. ($page - 1), true), 'prev');
			}

			if ($limit && ceil($product_total / $limit) > $page) {
			    $this->document->addLink($this->url->link('product/search', $url . '&page='. ($page + 1), true), 'next');
			}

			if (isset($this->request->get['search']) && $this->config->get('config_customer_search')) {
				$this->load->model('account/search');

				if ($this->customer->isLogged()) {
					$customer_id = $this->customer->getId();
				} else {
					$customer_id = 0;
				}

				if (isset($this->request->server['REMOTE_ADDR'])) {
					$ip = $this->request->server['REMOTE_ADDR'];
				} else {
					$ip = '';
				}

				$search_data = array(
					'keyword'       => $search,
					'category_id'   => $category_id,
					'sub_category'  => $sub_category,
					'description'   => $description,
					'products'      => $product_total,
					'customer_id'   => $customer_id,
					'ip'            => $ip
				);

				$this->model_account_search->addSearch($search_data);
			}
		}

		$data['search'] = $search;
		$data['description'] = $description;
		$data['category_id'] = $category_id;
		$data['sub_category'] = $sub_category;

		$data['sort'] = $sort;
		$data['order'] = $order;
		$data['limit'] = $limit;

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('product/search', $data));
	}
}
