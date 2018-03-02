<div>
    <ul class="pager">
  
        <!-- Previous page link -->
        <li class="previuous <?= (!isset($this->previous)) ? 'disabled' : ''; ?>"><a href="<?= $this->url(['page' => $this->previous]); ?>">&lt; Previous</a></li>

         
        <!-- Next page link -->
        <li class="next <?= (!isset($this->next)) ? 'disabled' : ''; ?>"><a href="<?= $this->url(['page' => $this->next]); ?>">Next &gt;</a></li>
         
    </ul>
 </div>