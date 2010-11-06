<?php
class Pagination {
    
    /**
     * Gets the current page for the specified pager
     * @return integer The current page
     */
    function getCurrentpage() {
        $_REQUEST->setType('page', 'numeric');
        if($_REQUEST['page'] && isset($_REQUEST['page']) && is_numeric($_REQUEST['page'])) {
            return $_REQUEST['page'];
        } else {
            return 1;
        }
    }
    
    /**
     * Returns a zero-based range of items to display
     * @param $perPage How many hits should be displayed per page
     * @return array Array with the fields 'range' (two field array with lower and upper bound) and 'links' (xHTML-formatted string with links to the other pages)
     */
    function getRange($perPage, $total) {
        $currentPage = self::getCurrentpage();
        $start = $perPage * ($currentPage - 1);
        return array('range' => array('start' => $start, 'stop' => $start + $perPage - 1), 'links' => self::getLinks($perPage, $total));
    }
    
    /**
     * Generate a XHTML-formatted string with pagination links
     * @param $perPage How many should be displayed per pages
     * @param $total How many items should be displayed
     * @return string XHTML-formatted pagination links.
     */
    function getLinks($perPage, $total) {
        $_REQUEST->setType('page', 'numeric');
        $currentPage = self::getCurrentpage();
        $totalPages = ceil($total/$perPage);
        
        $result = '<ul class="pagination">';
        
        $result .= '<li class="prev_page">';
        if($currentPage > 1) $result .= '<a href="'.url(array('page' => $currentPage-1), true).'">';
        $result .= __('Previous');
        if($currentPage > 1) $result .= '</a>';
        $result .= '</li>';
        
        $result .= '<li';
        if($currentPage == 1) $result .= ' class="current">1</li>';
        else $result .= '><a href="'.url(array('page' => 1), true).'">1</a></li>';
        
        $start = max($currentPage-5, 2);
        $stop  = min($currentPage+5, $totalPages-1);
        
        if($start > 2) $result .= '<li>...</li>';
        
        for($i=$start;$i<=$stop;$i++) {
            if($i == $currentPage) $result .= '<li class="current">'.$i.'</li>';
            else $result .= '<li><a href="'.url(array('page' => $i), true).'">'.$i.'</a></li>';
        }
        
        if($stop < $totalPages-1) $result .= '<li>...</li>';
        
        $result .= '<li';
        if($currentPage == $totalPages) $result .= ' class = "current">'.$totalPages.'</li>';
        else $result .= '><a href="'.url(array('page' => $totalPages), true).'">'.$totalPages.'</a></li>';
        
        $result .= '<li class="next_page">';
        if($currentPage < $totalPages) $result .= '<a href="'.url(array('page' => $currentPage+1), true).'">';
        $result .= __('Next');
        if($currentPage < $totalPages) $result .= '</a>';
        $result .= '</li></ul>';
        
        return $result;
    }

     /* Old version of getLinks */
    function getLinks_old($perPage, $total) {
        $_REQUEST->setType('page', 'numeric');
        
        $currentPage = self::getCurrentpage();
        $TotalPages = ceil($total / $perPage);
        
        $result = '<div class="paginationIndex">';
    
        if($currentPage > 2) {
            $result .= '<span><a href="'.url(array('page' => 1), true).'">&laquo;</a></span>';
        }
        if($currentPage > 1) {
            $result .= '<span><a href="'.url(array('page' => $currentPage-1), true).'">&laquo;</a></span>';
        }
        
        $start = max($currentPage-4, 1);
        $stop  = min($currentPage+4, $TotalPages);
        
        if($start > 1) {
            $result .= '...';
        }
        
        for($i = $start; $i<=$stop;$i++) {
            $result .= '<span'.($i == $currentPage ? ' class="currentPage"':'').'><a href="'.url(array('page' => $i)).'">'.$i.'</a></span>';
        }
        
        if($stop < $TotalPages) {
            $result .= '...';
        }
    
        if($currentPage < $TotalPages) {
            $result .= '<span><a href="'.url(array('page' => $currentPage+1), true).'">&gt;</a></span>';
        }
        if($currentPage < ($TotalPages-1)) {
            $result .= '<span><a href="'.url(array('page' => $TotalPages), true).'">&raquo;</a></span>';
        }
        
        $result .= '</div>';
        
        return $result;
    }
            
}
?>
